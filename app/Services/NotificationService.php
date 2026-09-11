<?php

namespace App\Services;

use App\Enums\ExpirationStatus;
use App\Enums\UserRole;
use App\Models\AuditLog;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * NotificationService — PRD FR-10, FR-11, FR-16
 *
 * Handles business logic for generating, updating, cleaning up, and querying contract expiration alerts.
 */
class NotificationService
{
    public function __construct(
        protected ExpirationService $expirationService
    ) {}

    /**
     * Process an array of enriched contracts and generate/update expiration alerts.
     *
     * @param array<int, array<string, mixed>> $contracts
     * @param int|null $targetUserId Specific user ID or null for all active AM users
     * @param bool $dryRun If true, calculate actions without modifying database
     * @return array{
     *     users_count: int,
     *     contracts_count: int,
     *     created: int,
     *     updated: int,
     *     cleaned: int,
     *     details: array<int, array<string, mixed>>
     * }
     */
    public function processContracts(array $contracts, ?int $targetUserId = null, bool $dryRun = false): array
    {
        // Resolve target users
        if ($targetUserId !== null) {
            $users = User::where('id', $targetUserId)->where('is_active', true)->get();
        } else {
            // Target all active AM users, fallback to all active users if no AM
            $users = User::active()->where('role', UserRole::AM)->get();
            if ($users->isEmpty()) {
                $users = User::active()->get();
            }
        }

        $summary = [
            'users_count' => $users->count(),
            'contracts_count' => count($contracts),
            'created' => 0,
            'updated' => 0,
            'cleaned' => 0,
            'details' => [],
        ];

        if ($users->isEmpty() || empty($contracts)) {
            return $summary;
        }

        // Ensure contracts are enriched with expiration status
        $enrichedContracts = $this->expirationService->enrichAll($contracts);

        foreach ($users as $user) {
            foreach ($enrichedContracts as $contract) {
                $lop = $contract['lop'] ?? null;
                $daysRemaining = $contract['days_remaining'] ?? null;
                $status = $contract['expiration_status'] ?? null;
                $contractStatus = $contract['status_kontrak'] ?? 'BERJALAN';

                if (empty($lop) || $daysRemaining === null) {
                    continue;
                }

                if ($contractStatus === 'SELESAI') {
                    // Contract is completed. Cleanup any stale alerts.
                    $cleanedCount = $dryRun ? 0 : $this->cleanupStaleNotifications($user->id, $lop);
                    if ($cleanedCount > 0) {
                        $summary['cleaned'] += $cleanedCount;
                        $summary['details'][] = [
                            'user_id' => $user->id,
                            'lop' => $lop,
                            'action' => 'cleaned',
                            'cleaned_count' => $cleanedCount,
                        ];
                    }
                    continue;
                }

                if ($status === ExpirationStatus::EXPIRING_SOON->value) {
                    $result = $this->handleExpiringSoon($user->id, $lop, $daysRemaining, $dryRun);
                    $summary[$result['action']]++;
                    $summary['details'][] = [
                        'user_id' => $user->id,
                        'lop' => $lop,
                        'alert_type' => 'EXPIRING_SOON',
                        'days_remaining' => $daysRemaining,
                        'action' => $result['action'],
                    ];
                } elseif ($status === ExpirationStatus::OVERDUE->value) {
                    $result = $this->handleOverdue($user->id, $lop, $daysRemaining, $dryRun);
                    $summary[$result['action']]++;
                    $summary['details'][] = [
                        'user_id' => $user->id,
                        'lop' => $lop,
                        'alert_type' => 'OVERDUE',
                        'days_remaining' => $daysRemaining,
                        'action' => $result['action'],
                    ];
                } elseif ($status === ExpirationStatus::ACTIVE->value) {
                    // Contract is now active (e.g. renewed / extended). Cleanup stale alerts.
                    $cleanedCount = $dryRun ? 0 : $this->cleanupStaleNotifications($user->id, $lop);
                    if ($cleanedCount > 0) {
                        $summary['cleaned'] += $cleanedCount;
                        $summary['details'][] = [
                            'user_id' => $user->id,
                            'lop' => $lop,
                            'action' => 'cleaned',
                            'cleaned_count' => $cleanedCount,
                        ];
                    }
                }
            }
        }

        return $summary;
    }

    /**
     * Handle EXPIRING_SOON notification logic.
     */
    protected function handleExpiringSoon(int $userId, string $lop, int $daysRemaining, bool $dryRun = false): array
    {
        if ($dryRun) {
            $exists = Notification::where('user_id', $userId)
                ->where('lop_reference', $lop)
                ->where('alert_type', 'EXPIRING_SOON')
                ->exists();

            return ['action' => $exists ? 'updated' : 'created'];
        }

        return $this->createOrUpdateNotification($userId, $lop, 'EXPIRING_SOON', $daysRemaining);
    }

    /**
     * Handle OVERDUE notification logic.
     */
    protected function handleOverdue(int $userId, string $lop, int $daysRemaining, bool $dryRun = false): array
    {
        if ($dryRun) {
            $exists = Notification::where('user_id', $userId)
                ->where('lop_reference', $lop)
                ->where('alert_type', 'OVERDUE')
                ->exists();

            return ['action' => $exists ? 'updated' : 'created'];
        }

        return $this->createOrUpdateNotification($userId, $lop, 'OVERDUE', $daysRemaining);
    }

    /**
     * Create or update a notification record with duplicate prevention.
     *
     * @return array{action: 'created'|'updated', notification: Notification}
     */
    public function createOrUpdateNotification(int $userId, string $lop, string $alertType, int $daysRemaining): array
    {
        $notification = Notification::where('user_id', $userId)
            ->where('lop_reference', $lop)
            ->where('alert_type', $alertType)
            ->first();

        if ($notification) {
            // If days_remaining changed, update it. If it changed significantly, re-alert (is_read = false)
            $oldDays = $notification->days_remaining;
            $notification->days_remaining = $daysRemaining;

            // If days changed (e.g. from H-30 to H-1 or into overdue), unmark as read so AM notices the update
            if ($oldDays !== $daysRemaining) {
                $notification->is_read = false;
            }

            $notification->save();

            return [
                'action' => 'updated',
                'notification' => $notification,
            ];
        }

        // Create new notification
        $notification = Notification::create([
            'user_id' => $userId,
            'lop_reference' => $lop,
            'alert_type' => $alertType,
            'days_remaining' => $daysRemaining,
            'is_read' => false,
        ]);

        // Record Audit Log (PRD FR-16)
        try {
            AuditLog::record(
                action: 'notification_generated',
                userId: $userId,
                targetType: 'notification',
                targetReference: $lop,
                metadata: [
                    'alert_type' => $alertType,
                    'days_remaining' => $daysRemaining,
                ]
            );
        } catch (Throwable $e) {
            Log::warning("Failed to record audit log for notification generation: " . $e->getMessage());
        }

        return [
            'action' => 'created',
            'notification' => $notification,
        ];
    }

    /**
     * Remove obsolete notifications when a contract is renewed (now ACTIVE).
     */
    public function cleanupStaleNotifications(int $userId, string $lop): int
    {
        return Notification::where('user_id', $userId)
            ->where('lop_reference', $lop)
            ->delete();
    }

    /**
     * Get unread notifications count for a user.
     */
    public function getUnreadCount(int $userId): int
    {
        return Notification::where('user_id', $userId)->unread()->count();
    }

    /**
     * Get notifications list for a user.
     */
    public function getNotificationsForUser(int $userId, int $limit = 20): Collection
    {
        return Notification::where('user_id', $userId)
            ->latest()
            ->take($limit)
            ->get();
    }

    /**
     * Mark a single notification as read.
     */
    public function markAsRead(int $notificationId, int $userId): bool
    {
        $notification = Notification::where('id', $notificationId)
            ->where('user_id', $userId)
            ->first();

        if (!$notification) {
            return false;
        }

        return $notification->markAsRead();
    }

    /**
     * Mark all notifications for a user as read.
     */
    public function markAllAsRead(int $userId): int
    {
        return Notification::where('user_id', $userId)
            ->unread()
            ->update(['is_read' => true]);
    }
}

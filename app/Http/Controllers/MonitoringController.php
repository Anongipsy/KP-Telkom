<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use App\Services\ContractService;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

/**
 * MonitoringController — PRD FR-09, FR-10, FR-11
 *
 * Consolidated Early Warning System & Notification Management Controller.
 */
class MonitoringController extends Controller
{
    public function __construct(
        protected ContractService $contractService,
        protected NotificationService $notificationService
    ) {}

    /**
     * Display the consolidated Early Warning & Notifications page.
     */
    public function index(Request $request): View
    {
        $user = $request->user();
        $errorMessage = null;
        $contracts = [];

        try {
            $contracts = $this->contractService->getAllContracts();
        } catch (\Throwable $e) {
            Log::error('Monitoring data retrieval error: ' . $e->getMessage(), [
                'user_id' => $user->id,
            ]);
            $errorMessage = 'Gagal memuat data kedaluwarsa kontrak dari Google Sheets. Silakan coba beberapa saat lagi.';
            $contracts = [];
        }

        $isNotCompleted = fn($c) => !($c['is_completed'] ?? false)
            && strtoupper((string) ($c['status_kontrak'] ?? 'BERJALAN')) !== 'SELESAI'
            && !str_contains(strtoupper((string) ($c['status_kontrak'] ?? '')), 'SELESAI');

        // Categorize contracts for Expiration Health monitoring (excluding completed contracts)
        $overdueContracts = array_values(array_filter($contracts, fn($c) =>
            ($c['expiration_status'] ?? '') === 'OVERDUE' && $isNotCompleted($c)
        ));
        $expiringSoonContracts = array_values(array_filter($contracts, fn($c) =>
            ($c['expiration_status'] ?? '') === 'EXPIRING_SOON' && $isNotCompleted($c)
        ));
        $activeContracts = array_values(array_filter($contracts, fn($c) =>
            ($c['expiration_status'] ?? '') === 'ACTIVE' && $isNotCompleted($c)
        ));

        // Query user notifications
        $notificationQuery = Notification::where('user_id', $user->id)->latest();

        $filterStatus = $request->input('status', 'all'); // 'all', 'unread', 'read'
        if ($filterStatus === 'unread') {
            $notificationQuery->unread();
        } elseif ($filterStatus === 'read') {
            $notificationQuery->read();
        }

        $filterType = $request->input('type'); // 'EXPIRING_SOON', 'OVERDUE'
        if ($filterType) {
            $notificationQuery->where('alert_type', $filterType);
        }

        $notifications = $notificationQuery->paginate(20)->withQueryString();
        $unreadCount = $this->notificationService->getUnreadCount($user->id);
        $readCount = Notification::where('user_id', $user->id)->read()->count();
        $totalNotificationCount = Notification::where('user_id', $user->id)->count();

        return view('monitoring.index', [
            'user' => $user,
            'overdueContracts' => $overdueContracts,
            'expiringSoonContracts' => $expiringSoonContracts,
            'activeContracts' => $activeContracts,
            'notifications' => $notifications,
            'unreadCount' => $unreadCount,
            'readCount' => $readCount,
            'totalNotificationCount' => $totalNotificationCount,
            'errorMessage' => $errorMessage,
            'activeTab' => $request->input('tab', 'expiration'), // 'expiration' or 'notifications'
            'filterStatus' => $filterStatus,
            'filterType' => $filterType,
        ]);
    }
}

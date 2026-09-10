<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\AuditLog;
use App\Models\Notification;
use App\Models\User;
use App\Services\ExpirationService;
use App\Services\NotificationService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NotificationServiceTest extends TestCase
{
    use RefreshDatabase;

    protected NotificationService $notificationService;
    protected ExpirationService $expirationService;
    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->expirationService = new ExpirationService();
        $this->notificationService = new NotificationService($this->expirationService);
        $this->user = User::factory()->create([
            'role' => UserRole::AM,
            'is_active' => true,
        ]);
    }

    protected function makeContract(string $lop, int $daysRemaining): array
    {
        $endDate = Carbon::now()->addDays($daysRemaining)->format('Y-m-d');

        return [
            'lop' => $lop,
            'contract_number' => 'CTR/' . $lop,
            'customer' => 'Customer ' . $lop,
            'end_date' => $endDate,
        ];
    }

    public function test_contract_h61_active_no_notification_generated(): void
    {
        $contracts = [$this->makeContract('LOP-H61', 61)];

        $summary = $this->notificationService->processContracts($contracts, $this->user->id);

        $this->assertEquals(0, $summary['created']);
        $this->assertEquals(0, $summary['updated']);
        $this->assertDatabaseMissing('notifications', [
            'lop_reference' => 'LOP-H61',
        ]);
    }

    public function test_contract_h60_expiring_soon_notification_created(): void
    {
        $contracts = [$this->makeContract('LOP-H60', 60)];

        $summary = $this->notificationService->processContracts($contracts, $this->user->id);

        $this->assertEquals(1, $summary['created']);
        $this->assertDatabaseHas('notifications', [
            'user_id' => $this->user->id,
            'lop_reference' => 'LOP-H60',
            'alert_type' => 'EXPIRING_SOON',
            'days_remaining' => 60,
            'is_read' => false,
        ]);
    }

    public function test_contract_h30_expiring_soon_notification_created(): void
    {
        $contracts = [$this->makeContract('LOP-H30', 30)];

        $summary = $this->notificationService->processContracts($contracts, $this->user->id);

        $this->assertEquals(1, $summary['created']);
        $this->assertDatabaseHas('notifications', [
            'user_id' => $this->user->id,
            'lop_reference' => 'LOP-H30',
            'alert_type' => 'EXPIRING_SOON',
            'days_remaining' => 30,
        ]);
    }

    public function test_contract_h1_expiring_soon_notification_created(): void
    {
        $contracts = [$this->makeContract('LOP-H1', 1)];

        $summary = $this->notificationService->processContracts($contracts, $this->user->id);

        $this->assertEquals(1, $summary['created']);
        $this->assertDatabaseHas('notifications', [
            'user_id' => $this->user->id,
            'lop_reference' => 'LOP-H1',
            'alert_type' => 'EXPIRING_SOON',
            'days_remaining' => 1,
        ]);
    }

    public function test_contract_h_plus_1_overdue_notification_created(): void
    {
        // -1 days remaining = Overdue by 1 day (H+1 from end date)
        $contracts = [$this->makeContract('LOP-OVERDUE-1', -1)];

        $summary = $this->notificationService->processContracts($contracts, $this->user->id);

        $this->assertEquals(1, $summary['created']);
        $this->assertDatabaseHas('notifications', [
            'user_id' => $this->user->id,
            'lop_reference' => 'LOP-OVERDUE-1',
            'alert_type' => 'OVERDUE',
            'days_remaining' => -1,
        ]);
    }

    public function test_duplicate_prevention_same_lop_and_alert_type_does_not_duplicate(): void
    {
        $contracts = [$this->makeContract('LOP-DUP-TEST', 45)];

        // Run first time
        $summary1 = $this->notificationService->processContracts($contracts, $this->user->id);
        $this->assertEquals(1, $summary1['created']);
        $this->assertEquals(0, $summary1['updated']);

        // Run second time with same days
        $summary2 = $this->notificationService->processContracts($contracts, $this->user->id);
        $this->assertEquals(0, $summary2['created']);
        $this->assertEquals(1, $summary2['updated']);

        // Verify only 1 record exists in DB
        $this->assertEquals(1, Notification::where('lop_reference', 'LOP-DUP-TEST')->count());
    }

    public function test_rerun_with_changed_days_updates_notification(): void
    {
        $contractsDay1 = [$this->makeContract('LOP-AGING', 30)];
        $this->notificationService->processContracts($contractsDay1, $this->user->id);

        // Mark as read by user
        $notif = Notification::where('lop_reference', 'LOP-AGING')->first();
        $notif->markAsRead();
        $this->assertTrue($notif->fresh()->is_read);

        // Run again 5 days later (25 days remaining)
        $contractsDay5 = [$this->makeContract('LOP-AGING', 25)];
        $summary = $this->notificationService->processContracts($contractsDay5, $this->user->id);

        $this->assertEquals(0, $summary['created']);
        $this->assertEquals(1, $summary['updated']);

        $updatedNotif = Notification::where('lop_reference', 'LOP-AGING')->first();
        $this->assertEquals(25, $updatedNotif->days_remaining);
        // Should be unread again to re-alert user of changed days
        $this->assertFalse($updatedNotif->is_read);
    }

    public function test_stale_notification_cleanup_when_contract_becomes_active(): void
    {
        // Step 1: Contract was expiring soon (20 days)
        $contractsExpiring = [$this->makeContract('LOP-RENEW', 20)];
        $this->notificationService->processContracts($contractsExpiring, $this->user->id);
        $this->assertDatabaseHas('notifications', ['lop_reference' => 'LOP-RENEW']);

        // Step 2: Contract renewed -> 365 days (ACTIVE)
        $contractsRenewed = [$this->makeContract('LOP-RENEW', 365)];
        $summary = $this->notificationService->processContracts($contractsRenewed, $this->user->id);

        $this->assertEquals(1, $summary['cleaned']);
        $this->assertDatabaseMissing('notifications', ['lop_reference' => 'LOP-RENEW']);
    }

    public function test_dry_run_mode_does_not_persist_to_database(): void
    {
        $contracts = [
            $this->makeContract('LOP-DRY-1', 15),
            $this->makeContract('LOP-DRY-2', -5),
        ];

        $summary = $this->notificationService->processContracts($contracts, $this->user->id, dryRun: true);

        $this->assertEquals(2, $summary['created']);
        $this->assertDatabaseMissing('notifications', ['lop_reference' => 'LOP-DRY-1']);
        $this->assertDatabaseMissing('notifications', ['lop_reference' => 'LOP-DRY-2']);
    }

    public function test_mark_as_read_and_mark_all_as_read(): void
    {
        Notification::factory()->create([
            'user_id' => $this->user->id,
            'is_read' => false,
        ]);
        Notification::factory()->create([
            'user_id' => $this->user->id,
            'is_read' => false,
        ]);

        $this->assertEquals(2, $this->notificationService->getUnreadCount($this->user->id));

        $this->notificationService->markAllAsRead($this->user->id);

        $this->assertEquals(0, $this->notificationService->getUnreadCount($this->user->id));
    }

    public function test_audit_log_recorded_on_notification_generation(): void
    {
        $contracts = [$this->makeContract('LOP-AUDIT-TEST', 10)];

        $this->notificationService->processContracts($contracts, $this->user->id);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'notification_generated',
            'user_id' => $this->user->id,
            'target_type' => 'notification',
            'target_reference' => 'LOP-AUDIT-TEST',
        ]);
    }
}

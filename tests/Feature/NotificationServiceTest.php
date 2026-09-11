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

    public function test_user_can_delete_single_notification(): void
    {
        $notif = Notification::factory()->create([
            'user_id' => $this->user->id,
            'lop_reference' => 'LOP-DEL-1',
        ]);

        $response = $this->actingAs($this->user)->delete('/notifications/' . $notif->id);

        $response->assertStatus(302);
        $this->assertDatabaseMissing('notifications', ['id' => $notif->id]);
    }

    public function test_user_can_clear_all_notifications(): void
    {
        Notification::factory()->count(3)->create([
            'user_id' => $this->user->id,
        ]);

        $this->assertCount(3, Notification::where('user_id', $this->user->id)->get());

        $response = $this->actingAs($this->user)->delete('/notifications/clear-all');

        $response->assertStatus(302);
        $this->assertCount(0, Notification::where('user_id', $this->user->id)->get());
    }

    public function test_filter_notifications_by_read_and_unread_status(): void
    {
        Notification::factory()->create([
            'user_id' => $this->user->id,
            'is_read' => true,
            'lop_reference' => 'LOP-READ-1',
        ]);
        Notification::factory()->create([
            'user_id' => $this->user->id,
            'is_read' => false,
            'lop_reference' => 'LOP-UNREAD-1',
        ]);

        // Test status=read
        $responseRead = $this->actingAs($this->user)->get('/monitoring?tab=notifications&status=read');
        $responseRead->assertStatus(200);
        $notificationsRead = $responseRead->viewData('notifications');
        $this->assertCount(1, $notificationsRead);
        $this->assertTrue((bool) $notificationsRead->first()->is_read);
        $this->assertEquals('LOP-READ-1', $notificationsRead->first()->lop_reference);

        // Test status=unread
        $responseUnread = $this->actingAs($this->user)->get('/monitoring?tab=notifications&status=unread');
        $responseUnread->assertStatus(200);
        $notificationsUnread = $responseUnread->viewData('notifications');
        $this->assertCount(1, $notificationsUnread);
        $this->assertFalse((bool) $notificationsUnread->first()->is_read);
        $this->assertEquals('LOP-UNREAD-1', $notificationsUnread->first()->lop_reference);

        // Test status=all
        $responseAll = $this->actingAs($this->user)->get('/monitoring?tab=notifications&status=all');
        $responseAll->assertStatus(200);
        $notificationsAll = $responseAll->viewData('notifications');
        $this->assertCount(2, $notificationsAll);
    }

    public function test_completed_contract_does_not_generate_notification_and_cleans_up_stale(): void
    {
        // First create an existing notification for a contract
        Notification::factory()->create([
            'user_id' => $this->user->id,
            'alert_type' => 'OVERDUE',
            'lop_reference' => 'LOP-COMPLETED-1',
            'days_remaining' => -10,
        ]);

        $this->assertDatabaseHas('notifications', [
            'lop_reference' => 'LOP-COMPLETED-1',
        ]);

        // Contract is overdue (-10 days) but status_kontrak is SELESAI
        $contract = $this->makeContract('LOP-COMPLETED-1', -10);
        $contract['status_kontrak'] = 'SELESAI';

        $summary = $this->notificationService->processContracts([$contract], $this->user->id);

        // Should not create or update, but should cleanup the stale notification
        $this->assertEquals(0, $summary['created']);
        $this->assertEquals(0, $summary['updated']);
        $this->assertEquals(1, $summary['cleaned']);
        $this->assertDatabaseMissing('notifications', [
            'lop_reference' => 'LOP-COMPLETED-1',
        ]);
    }
}

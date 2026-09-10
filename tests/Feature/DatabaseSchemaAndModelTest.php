<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\ApplicationSetting;
use App\Models\AuditLog;
use App\Models\Notification;
use App\Models\SyncLog;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DatabaseSchemaAndModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_model_and_role_helpers(): void
    {
        $amUser = User::factory()->am()->create([
            'email' => 'am@example.com',
        ]);

        $adminUser = User::factory()->admin()->create([
            'email' => 'admin@example.com',
        ]);

        $this->assertTrue($amUser->isAm());
        $this->assertFalse($amUser->isAdmin());
        $this->assertEquals(UserRole::AM, $amUser->role);

        $this->assertTrue($adminUser->isAdmin());
        $this->assertFalse($adminUser->isAm());
        $this->assertEquals(UserRole::ADMIN, $adminUser->role);

        $inactiveUser = User::factory()->inactive()->create();
        $this->assertFalse($inactiveUser->is_active);

        $this->assertCount(2, User::active()->get());
    }

    public function test_user_relationships(): void
    {
        $user = User::factory()->create();

        $notification = Notification::factory()->create(['user_id' => $user->id]);
        $syncLog = SyncLog::factory()->create(['user_id' => $user->id]);
        $auditLog = AuditLog::factory()->create(['user_id' => $user->id]);

        $this->assertCount(1, $user->contractNotifications);
        $this->assertEquals($notification->id, $user->contractNotifications->first()->id);

        $this->assertCount(1, $user->syncLogs);
        $this->assertEquals($syncLog->id, $user->syncLogs->first()->id);

        $this->assertCount(1, $user->auditLogs);
        $this->assertEquals($auditLog->id, $user->auditLogs->first()->id);

        $this->assertEquals($user->id, $notification->user->id);
        $this->assertEquals($user->id, $syncLog->user->id);
        $this->assertEquals($user->id, $auditLog->user->id);
    }

    public function test_notification_model_scopes_and_helpers(): void
    {
        $user = User::factory()->create();

        $unread = Notification::factory()->expiringSoon()->create([
            'user_id' => $user->id,
            'is_read' => false,
        ]);

        $read = Notification::factory()->overdue()->read()->create([
            'user_id' => $user->id,
        ]);

        $this->assertCount(1, Notification::unread()->get());
        $this->assertEquals($unread->id, Notification::unread()->first()->id);

        $this->assertCount(1, Notification::ofType('EXPIRING_SOON')->get());
        $this->assertCount(1, Notification::ofType('OVERDUE')->get());

        $this->assertCount(1, Notification::forLop($unread->lop_reference)->get());

        $unread->markAsRead();
        $this->assertTrue($unread->fresh()->is_read);
        $this->assertCount(0, Notification::unread()->get());
    }

    public function test_notification_duplicate_prevention_constraint(): void
    {
        $user = User::factory()->create();

        Notification::create([
            'user_id' => $user->id,
            'lop_reference' => 'LOP-001',
            'alert_type' => 'EXPIRING_SOON',
            'days_remaining' => 15,
            'is_read' => false,
        ]);

        // Attempting to create duplicate notification for same user + lop + alert_type should fail
        $this->expectException(QueryException::class);

        Notification::create([
            'user_id' => $user->id,
            'lop_reference' => 'LOP-001',
            'alert_type' => 'EXPIRING_SOON',
            'days_remaining' => 14,
            'is_read' => false,
        ]);
    }

    public function test_sync_log_model_scopes_and_attributes(): void
    {
        $user = User::factory()->create();

        $successSync = SyncLog::factory()->successful()->fromSheets()->create([
            'user_id' => $user->id,
            'records_processed' => 25,
        ]);

        $failedSync = SyncLog::factory()->failed()->toSheets()->create([
            'user_id' => $user->id,
        ]);

        $this->assertCount(1, SyncLog::direction('SHEETS_TO_APP')->get());
        $this->assertCount(1, SyncLog::direction('APP_TO_SHEETS')->get());
        $this->assertCount(1, SyncLog::status('success')->get());
        $this->assertCount(1, SyncLog::failed()->get());

        $this->assertNotNull($successSync->duration);
        $this->assertIsInt($successSync->duration);
    }

    public function test_audit_log_model_and_record_helper(): void
    {
        $user = User::factory()->create();

        $log = AuditLog::record(
            action: 'contract_update',
            userId: $user->id,
            targetType: 'contract',
            targetReference: 'LOP-1234',
            metadata: ['field' => 'revenue', 'old' => 1000, 'new' => 2000]
        );

        $this->assertDatabaseHas('audit_logs', [
            'id' => $log->id,
            'action' => 'contract_update',
            'target_type' => 'contract',
            'target_reference' => 'LOP-1234',
        ]);

        $this->assertEquals(['field' => 'revenue', 'old' => 1000, 'new' => 2000], $log->fresh()->metadata);
        $this->assertCount(1, AuditLog::action('contract_update')->get());
        $this->assertCount(1, AuditLog::forTarget('contract', 'LOP-1234')->get());
    }

    public function test_application_setting_helpers(): void
    {
        ApplicationSetting::setValue('test_key', 'initial_value');
        $this->assertEquals('initial_value', ApplicationSetting::getValue('test_key'));

        ApplicationSetting::setValue('test_key', 'updated_value');
        $this->assertEquals('updated_value', ApplicationSetting::getValue('test_key'));

        $this->assertEquals('default_value', ApplicationSetting::getValue('non_existent_key', 'default_value'));
    }

    public function test_database_seeder_runs_successfully(): void
    {
        $this->seed();

        $this->assertDatabaseHas('users', [
            'email' => 'am@telkom.co.id',
            'role' => UserRole::AM->value,
            'is_active' => true,
        ]);

        $this->assertDatabaseHas('users', [
            'email' => 'admin@telkom.co.id',
            'role' => UserRole::ADMIN->value,
            'is_active' => true,
        ]);

        $this->assertEquals('1.0.0', ApplicationSetting::getValue('app_version'));
    }
}

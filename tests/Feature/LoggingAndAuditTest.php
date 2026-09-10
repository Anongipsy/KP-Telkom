<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Helpers\LogSanitizer;
use App\Models\AuditLog;
use App\Models\SyncLog;
use App\Models\User;
use App\Services\GoogleDriveService;
use Google\Service\Drive as GoogleDrive;
use Google\Service\Drive\Resource\Files as DriveFilesResource;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class LoggingAndAuditTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;
    protected User $amUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->adminUser = User::factory()->create([
            'email' => 'admin@telkom.co.id',
            'role' => UserRole::ADMIN,
            'is_active' => true,
        ]);

        $this->amUser = User::factory()->create([
            'email' => 'am@telkom.co.id',
            'role' => UserRole::AM,
            'is_active' => true,
        ]);
    }

    public function test_login_creates_audit_log_with_who_what_when_target(): void
    {
        $response = $this->post('/login', [
            'email' => 'am@telkom.co.id',
            'password' => 'password',
        ]);

        $response->assertRedirect('/dashboard');

        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $this->amUser->id,
            'action' => 'login',
            'target_type' => 'user',
            'target_reference' => 'am@telkom.co.id',
        ]);

        $log = AuditLog::where('action', 'login')->where('user_id', $this->amUser->id)->first();
        $this->assertNotNull($log->created_at);
        $this->assertArrayNotHasKey('password', $log->metadata ?? []);
    }

    public function test_failed_login_creates_audit_log_without_exposing_password(): void
    {
        $response = $this->post('/login', [
            'email' => 'am@telkom.co.id',
            'password' => 'wrong_secret_password_123',
        ]);

        $response->assertSessionHasErrors('email');

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'failed_login',
            'target_type' => 'user',
            'target_reference' => 'am@telkom.co.id',
        ]);

        $log = AuditLog::where('action', 'failed_login')->first();
        $this->assertNotNull($log);
        $this->assertArrayNotHasKey('password', $log->metadata ?? []);
        $this->assertStringNotContainsString('wrong_secret_password_123', json_encode($log->metadata));
    }

    public function test_sensitive_data_is_automatically_redacted_from_audit_log_metadata(): void
    {
        AuditLog::record(
            action: 'test_security_sanitization',
            userId: $this->amUser->id,
            targetType: 'test',
            targetReference: 'REF-001',
            metadata: [
                'password' => 'super_secret',
                'private_key' => '-----BEGIN PRIVATE KEY----- secret -----END PRIVATE KEY-----',
                'api_token' => 'bearer token 12345',
                'safe_field' => 'visible_value',
            ]
        );

        $log = AuditLog::where('action', 'test_security_sanitization')->first();
        $this->assertNotNull($log);

        $metadata = $log->metadata;
        $this->assertEquals('[REDACTED]', $metadata['password']);
        $this->assertEquals('[REDACTED]', $metadata['private_key']);
        $this->assertEquals('[REDACTED]', $metadata['api_token']);
        $this->assertEquals('visible_value', $metadata['safe_field']);
    }

    public function test_log_sanitizer_masks_sensitive_keys_and_string_patterns(): void
    {
        $input = [
            'username' => 'adit',
            'password' => 'myPassword123',
            'google_private_key' => 'secret_key',
            'access_token' => 'xyz789',
            'nested' => [
                'secret' => 'nested_secret',
                'normal' => 'keep_this',
            ],
        ];

        $sanitized = LogSanitizer::sanitize($input);

        $this->assertEquals('adit', $sanitized['username']);
        $this->assertEquals('[REDACTED]', $sanitized['password']);
        $this->assertEquals('[REDACTED]', $sanitized['google_private_key']);
        $this->assertEquals('[REDACTED]', $sanitized['access_token']);
        $this->assertEquals('[REDACTED]', $sanitized['nested']['secret']);
        $this->assertEquals('keep_this', $sanitized['nested']['normal']);
    }

    public function test_google_drive_api_error_records_sync_log(): void
    {
        $driveService = new GoogleDriveService();

        $mockFilesResource = Mockery::mock(DriveFilesResource::class);
        $mockFilesResource->shouldReceive('get')
            ->once()
            ->andThrow(new \Google\Service\Exception('Drive API Connection Failed', 500));

        $mockDrive = Mockery::mock(GoogleDrive::class);
        $mockDrive->files = $mockFilesResource;

        $driveService->setDriveService($mockDrive);

        try {
            $driveService->getFileMetadata('1a2B3c4D5e6F7g8H9i0J');
        } catch (\RuntimeException) {
            // Expected
        }

        $this->assertDatabaseHas('sync_logs', [
            'sync_type' => 'DRIVE_GET_METADATA',
            'status' => 'FAILED',
        ]);
    }

    public function test_admin_can_view_audit_logs(): void
    {
        AuditLog::record('test_action', $this->amUser->id, 'contract', 'LOP-001');

        $response = $this->actingAs($this->adminUser)->get('/admin/audit-logs');

        $response->assertOk();
        $response->assertSee('Audit Trail & Activity Log');
        $response->assertSee('TEST_ACTION');
        $response->assertSee('LOP-001');
        $response->assertSee($this->amUser->name);
    }

    public function test_am_cannot_access_admin_audit_logs(): void
    {
        $response = $this->actingAs($this->amUser)->get('/admin/audit-logs');

        $response->assertForbidden();
    }

    public function test_guest_cannot_access_admin_audit_logs(): void
    {
        $response = $this->get('/admin/audit-logs');

        $response->assertRedirect('/login');
    }

    public function test_admin_can_filter_audit_logs_by_action_and_search(): void
    {
        AuditLog::record('login', $this->amUser->id, 'user', 'user_unrelated_target@telkom.co.id');
        AuditLog::record('contract_create', $this->amUser->id, 'contract', 'LOP-FILTER-TARGET');

        $response = $this->actingAs($this->adminUser)->get('/admin/audit-logs?q=LOP-FILTER-TARGET');

        $response->assertOk();
        $response->assertSee('LOP-FILTER-TARGET');
        $response->assertDontSee('user_unrelated_target@telkom.co.id');
    }
}

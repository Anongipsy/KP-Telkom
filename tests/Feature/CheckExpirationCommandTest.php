<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Notification;
use App\Models\SyncLog;
use App\Models\User;
use App\Services\ContractService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use RuntimeException;
use Tests\TestCase;

class CheckExpirationCommandTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create([
            'role' => UserRole::AM,
            'is_active' => true,
        ]);
    }

    protected function getSampleContracts(): array
    {
        return [
            [
                'lop' => 'LOP-EXPIRING-1',
                'contract_number' => 'CTR/001',
                'customer' => 'PT Customer A',
                'end_date' => Carbon::now()->addDays(20)->format('Y-m-d'),
            ],
            [
                'lop' => 'LOP-OVERDUE-1',
                'contract_number' => 'CTR/002',
                'customer' => 'PT Customer B',
                'end_date' => Carbon::now()->subDays(5)->format('Y-m-d'),
            ],
            [
                'lop' => 'LOP-ACTIVE-1',
                'contract_number' => 'CTR/003',
                'customer' => 'PT Customer C',
                'end_date' => Carbon::now()->addDays(120)->format('Y-m-d'),
            ],
        ];
    }

    public function test_command_runs_successfully_and_outputs_summary(): void
    {
        $mockContractService = Mockery::mock(ContractService::class);
        $mockContractService->shouldReceive('getAllContracts')
            ->once()
            ->andReturn($this->getSampleContracts());

        $this->app->instance(ContractService::class, $mockContractService);

        $this->artisan('contracts:check-expiration')
            ->expectsOutputToContain('Contract Expiration Early Warning System')
            ->expectsOutputToContain('Total Kontrak Dievaluasi')
            ->expectsOutputToContain('Pengecekan masa berlaku kontrak selesai dengan sukses.')
            ->assertSuccessful();

        $this->assertDatabaseHas('notifications', [
            'user_id' => $this->user->id,
            'lop_reference' => 'LOP-EXPIRING-1',
            'alert_type' => 'EXPIRING_SOON',
        ]);

        $this->assertDatabaseHas('notifications', [
            'user_id' => $this->user->id,
            'lop_reference' => 'LOP-OVERDUE-1',
            'alert_type' => 'OVERDUE',
        ]);

        $this->assertDatabaseMissing('notifications', [
            'lop_reference' => 'LOP-ACTIVE-1',
        ]);
    }

    public function test_command_dry_run_does_not_modify_database(): void
    {
        $mockContractService = Mockery::mock(ContractService::class);
        $mockContractService->shouldReceive('getAllContracts')
            ->once()
            ->andReturn($this->getSampleContracts());

        $this->app->instance(ContractService::class, $mockContractService);

        $this->artisan('contracts:check-expiration', ['--dry-run' => true])
            ->expectsOutputToContain('DRY-RUN')
            ->assertSuccessful();

        $this->assertEquals(0, Notification::count());
    }

    public function test_command_is_idempotent_on_rerun(): void
    {
        $mockContractService = Mockery::mock(ContractService::class);
        $mockContractService->shouldReceive('getAllContracts')
            ->twice()
            ->andReturn($this->getSampleContracts());

        $this->app->instance(ContractService::class, $mockContractService);

        // Run 1
        $this->artisan('contracts:check-expiration')->assertSuccessful();
        $countAfterFirst = Notification::count();
        $this->assertEquals(2, $countAfterFirst);

        // Run 2
        $this->artisan('contracts:check-expiration')->assertSuccessful();
        $countAfterSecond = Notification::count();
        $this->assertEquals(2, $countAfterSecond);
    }

    public function test_command_handles_empty_contracts(): void
    {
        $mockContractService = Mockery::mock(ContractService::class);
        $mockContractService->shouldReceive('getAllContracts')
            ->once()
            ->andReturn([]);

        $this->app->instance(ContractService::class, $mockContractService);

        $this->artisan('contracts:check-expiration')
            ->expectsOutputToContain('Berhasil memuat 0 kontrak.')
            ->assertSuccessful();
    }

    public function test_command_records_sync_and_audit_logs(): void
    {
        $mockContractService = Mockery::mock(ContractService::class);
        $mockContractService->shouldReceive('getAllContracts')
            ->once()
            ->andReturn($this->getSampleContracts());

        $this->app->instance(ContractService::class, $mockContractService);

        $this->artisan('contracts:check-expiration')->assertSuccessful();

        $this->assertDatabaseHas('sync_logs', [
            'sync_type' => 'EXPIRATION_CHECK',
            'status' => 'SUCCESS',
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'expiration_check_completed',
        ]);
    }

    public function test_command_handles_api_failure_gracefully(): void
    {
        $mockContractService = Mockery::mock(ContractService::class);
        $mockContractService->shouldReceive('getAllContracts')
            ->once()
            ->andThrow(new RuntimeException('Google API Timeout'));

        $this->app->instance(ContractService::class, $mockContractService);

        $this->artisan('contracts:check-expiration')
            ->expectsOutputToContain('Gagal menjalankan pengecekan masa berlaku kontrak')
            ->assertFailed();

        $this->assertDatabaseHas('sync_logs', [
            'sync_type' => 'EXPIRATION_CHECK',
            'status' => 'FAILED',
        ]);
    }
}

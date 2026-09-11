<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\User;
use App\Services\ContractService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class NavigationAndPagesTest extends TestCase
{
    use RefreshDatabase;

    protected User $amUser;
    protected User $adminUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->amUser = User::factory()->create([
            'email' => 'am@telkom.co.id',
            'role' => UserRole::AM,
            'is_active' => true,
        ]);

        $this->adminUser = User::factory()->create([
            'email' => 'admin@telkom.co.id',
            'role' => UserRole::ADMIN,
            'is_active' => true,
        ]);
    }

    protected function getSampleContracts(): array
    {
        return [
            [
                '_row_index' => 2,
                'lop' => 'LOP-001',
                'contract_number' => 'CTR/001',
                'customer' => 'PT Telekomunikasi Indonesia',
                'satker' => 'Satker Regional 1',
                'service' => 'Astinet High Speed',
                'stage' => 'F4',
                'stage_label' => 'Negotiation',
                'revenue' => 100000000,
                'revenue_formatted' => 'Rp 100.000.000',
                'start_date' => '2026-01-01',
                'end_date' => Carbon::now()->addDays(90)->format('Y-m-d'),
                'days_remaining' => 90,
                'expiration_status' => 'ACTIVE',
                'sp_po' => 'AVAILABLE',
                'invoice_status' => 'ISSUED',
                'billcomp_status' => 'COMPLETED',
                'billcomp_percentage' => 100,
                'realized_revenue' => 100000000,
                'realized_revenue_formatted' => 'Rp 100.000.000',
                'document_reference' => 'drive_id_001',
            ],
            [
                '_row_index' => 3,
                'lop' => 'LOP-002',
                'contract_number' => 'CTR/002',
                'customer' => 'PT Bank Mandiri',
                'satker' => 'Satker Jakarta',
                'service' => 'SD-WAN',
                'stage' => 'F2',
                'stage_label' => 'Quote',
                'revenue' => 200000000,
                'revenue_formatted' => 'Rp 200.000.000',
                'start_date' => '2026-01-01',
                'end_date' => Carbon::now()->addDays(30)->format('Y-m-d'),
                'days_remaining' => 30,
                'expiration_status' => 'EXPIRING_SOON',
                'sp_po' => 'MISSING',
                'invoice_status' => 'UNBILLED',
                'billcomp_status' => 'NOT_COMPLETE',
                'billcomp_percentage' => 0,
                'realized_revenue' => 0,
                'realized_revenue_formatted' => 'Rp 0',
                'document_reference' => null,
            ],
        ];
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get('/dashboard')->assertRedirect('/login');
        $this->get('/contracts')->assertRedirect('/login');
        $this->get('/monitoring')->assertRedirect('/login');
        $this->get('/admin/audit-logs')->assertRedirect('/login');
    }

    public function test_authenticated_user_can_access_dashboard_overview(): void
    {
        $mockContractService = Mockery::mock(ContractService::class);
        $mockContractService->shouldReceive('getAllContracts')->once()->andReturn($this->getSampleContracts());
        $mockContractService->shouldReceive('getKpiSummary')->once()->andReturn([
            'total_pipeline_revenue_formatted' => 'Rp 300.000.000',
            'total_realized_revenue_formatted' => 'Rp 100.000.000',
            'realized_percentage' => 33.3,
            'total_lop' => 2,
            'expiring_soon_contracts' => 1,
            'overdue_contracts' => 0,
        ]);
        $mockContractService->shouldReceive('getContractsByStage')->once()->andReturn([]);

        $this->app->instance(ContractService::class, $mockContractService);

        $response = $this->actingAs($this->amUser)->get('/dashboard');

        $response->assertOk();
        $response->assertViewIs('dashboard.index');
        $response->assertSee('Dashboard (Overview)', false);
        $response->assertSee('Kontrak & Pipeline', false);
        $response->assertSee('Early Warning & Alerts', false);
    }

    public function test_authenticated_user_can_access_contracts_and_pipeline_page(): void
    {
        $mockContractService = Mockery::mock(ContractService::class);
        $mockContractService->shouldReceive('getAllContracts')->once()->andReturn($this->getSampleContracts());
        $mockContractService->shouldReceive('getContractsByStage')->once()->andReturn([]);
        $mockContractService->shouldReceive('searchAndFilter')->once()->andReturn($this->getSampleContracts());
        $mockContractService->shouldReceive('sortContracts')->once()->andReturn($this->getSampleContracts());

        $this->app->instance(ContractService::class, $mockContractService);

        $response = $this->actingAs($this->amUser)->get('/contracts');

        $response->assertOk();
        $response->assertViewIs('contracts.index');
        $response->assertSee('Manajemen Kontrak & Pipeline', false);
        $response->assertSee('Tabel Data', false);
        $response->assertSee('Papan Kanban (F0–F4)', false);
        $response->assertSee('Satker Regional 1', false);
        $response->assertSee('Satker Jakarta', false);
    }

    public function test_authenticated_user_can_access_monitoring_page(): void
    {
        $mockContractService = Mockery::mock(ContractService::class);
        $mockContractService->shouldReceive('getAllContracts')->once()->andReturn($this->getSampleContracts());

        $this->app->instance(ContractService::class, $mockContractService);

        $response = $this->actingAs($this->amUser)->get('/monitoring');

        $response->assertOk();
        $response->assertViewIs('monitoring.index');
        $response->assertSee('Early Warning & Pusat Notifikasi', false);
        $response->assertSee('Status Kedaluwarsa', false);
        $response->assertSee('Pusat Notifikasi', false);
    }

    public function test_sidebar_displays_admin_link_only_for_admin_users(): void
    {
        $mockContractService = Mockery::mock(ContractService::class);
        $mockContractService->shouldReceive('getAllContracts')->andReturn([]);
        $mockContractService->shouldReceive('getKpiSummary')->andReturn([]);
        $mockContractService->shouldReceive('getContractsByStage')->andReturn([]);
        $this->app->instance(ContractService::class, $mockContractService);

        // AM User
        $amResponse = $this->actingAs($this->amUser)->get('/dashboard');
        $amResponse->assertDontSee('Audit Trail & Log', false);

        // Admin User
        $adminResponse = $this->actingAs($this->adminUser)->get('/dashboard');
        $adminResponse->assertSee('Audit Trail & Log', false);
    }

    public function test_contracts_table_header_is_tahapan_and_filter_status_kontrak_exists(): void
    {
        $mockContractService = Mockery::mock(ContractService::class);
        $mockContractService->shouldReceive('getAllContracts')->once()->andReturn([]);
        $mockContractService->shouldReceive('getContractsByStage')->once()->andReturn([]);
        $mockContractService->shouldReceive('searchAndFilter')->once()->andReturn([]);
        $mockContractService->shouldReceive('sortContracts')->once()->andReturn([]);

        $this->app->instance(ContractService::class, $mockContractService);

        $response = $this->actingAs($this->amUser)->get('/contracts');
        $response->assertOk();
        $response->assertSee('<th class="px-4 py-3.5">Tahapan</th>', false);
        $response->assertDontSee('<th class="px-4 py-3.5">Tahapan & Status</th>', false);
        $response->assertSee('name="status_kontrak"', false);
    }

    public function test_completed_contracts_display_kontrak_selesai_badge(): void
    {
        $contracts = [
            [
                '_row_index' => 2,
                'lop' => 'LOP-COMP-1',
                'contract_number' => 'CTR/COMP/1',
                'satker' => 'Satker Selesai',
                'service' => 'Internet',
                'stage' => 'F4',
                'stage_label' => 'Closed Won',
                'revenue' => 10000000,
                'revenue_formatted' => 'Rp 10.000.000',
                'start_date' => '2025-01-01',
                'end_date' => '2025-12-31',
                'days_remaining' => -250,
                'expiration_status' => 'OVERDUE',
                'sp_po' => 'AVAILABLE',
                'status_kontrak' => 'SELESAI',
                'billcomp_percentage' => 100,
                'nilai_bc' => 10000000,
                'nilai_bc_formatted' => 'Rp 10.000.000',
            ],
        ];

        $mockContractService = Mockery::mock(ContractService::class);
        $mockContractService->shouldReceive('getAllContracts')->once()->andReturn($contracts);
        $mockContractService->shouldReceive('getContractsByStage')->once()->andReturn([]);
        $mockContractService->shouldReceive('searchAndFilter')->once()->andReturn($contracts);
        $mockContractService->shouldReceive('sortContracts')->once()->andReturn($contracts);

        $this->app->instance(ContractService::class, $mockContractService);

        $response = $this->actingAs($this->amUser)->get('/contracts');
        $response->assertOk();
        $response->assertSee('Kontrak Selesai', false);
        $response->assertDontSee('-250 hari', false);
    }

    public function test_monitoring_page_excludes_completed_contracts_from_overdue(): void
    {
        $contracts = [
            [
                '_row_index' => 2,
                'lop' => 'LOP-OVERDUE-ACTIVE',
                'contract_number' => 'CTR/001',
                'satker' => 'Satker Overdue',
                'service' => 'Astinet High Speed',
                'end_date' => '2025-12-31',
                'days_remaining' => -50,
                'expiration_status' => 'OVERDUE',
                'status_kontrak' => 'BERJALAN',
            ],
            [
                '_row_index' => 3,
                'lop' => 'LOP-OVERDUE-DONE',
                'contract_number' => 'CTR/002',
                'satker' => 'Satker Done',
                'service' => 'Astinet High Speed',
                'end_date' => '2025-12-31',
                'days_remaining' => -200,
                'expiration_status' => 'OVERDUE',
                'status_kontrak' => 'SELESAI',
            ],
        ];

        $mockContractService = Mockery::mock(ContractService::class);
        $mockContractService->shouldReceive('getAllContracts')->once()->andReturn($contracts);

        $this->app->instance(ContractService::class, $mockContractService);

        $response = $this->actingAs($this->amUser)->get('/monitoring');
        $response->assertOk();

        $overdueContracts = $response->viewData('overdueContracts');
        $this->assertCount(1, $overdueContracts);
        $this->assertEquals('LOP-OVERDUE-ACTIVE', $overdueContracts[0]['lop']);
    }
}

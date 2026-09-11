<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Notification;
use App\Models\User;
use App\Services\ContractService;
use App\Services\GoogleSheetsService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    protected function getSampleContracts(): array
    {
        return [
            [
                '_row_index' => 2,
                'lop' => 'LOP-101',
                'contract_number' => 'CTR/101',
                'customer' => 'PT Telekomunikasi Selular',
                'satker' => 'Satker Jakarta',
                'service' => 'Astinet',
                'stage' => 'F3',
                'stage_label' => 'Bidding',
                'revenue' => 150000000,
                'revenue_formatted' => 'Rp 150.000.000',
                'start_date' => '2026-01-01',
                'end_date' => Carbon::now()->addDays(90)->format('Y-m-d'),
                'days_remaining' => 90,
                'expiration_status' => 'ACTIVE',
                'is_active_contract' => true,
                'is_expiring_soon' => false,
                'is_overdue' => false,
                'sp_po' => 'AVAILABLE',
                'invoice_status' => 'ISSUED',
                'invoice_status_label' => 'Issued',
                'billcomp_status' => 'PARTIAL',
                'billcomp_status_label' => 'Partial',
                'billcomp_percentage' => 50,
                'realized_revenue' => 75000000,
                'realized_revenue_formatted' => 'Rp 75.000.000',
                'document_reference' => 'drive_id_101',
            ],
            [
                '_row_index' => 3,
                'lop' => 'LOP-102',
                'contract_number' => 'CTR/102',
                'customer' => 'PT Bank Mandiri',
                'satker' => 'Satker Bandung',
                'service' => 'Indibiz',
                'stage' => 'F4',
                'stage_label' => 'Negotiation',
                'revenue' => 80000000,
                'revenue_formatted' => 'Rp 80.000.000',
                'start_date' => '2026-02-01',
                'end_date' => Carbon::now()->addDays(15)->format('Y-m-d'),
                'days_remaining' => 15,
                'expiration_status' => 'EXPIRING_SOON',
                'is_active_contract' => false,
                'is_expiring_soon' => true,
                'is_overdue' => false,
                'sp_po' => 'AVAILABLE',
                'invoice_status' => 'PAID',
                'invoice_status_label' => 'Paid',
                'billcomp_status' => 'COMPLETED',
                'billcomp_status_label' => 'Completed',
                'billcomp_percentage' => 100,
                'realized_revenue' => 80000000,
                'realized_revenue_formatted' => 'Rp 80.000.000',
                'document_reference' => '',
            ],
        ];
    }

    public function test_guest_cannot_access_dashboard(): void
    {
        $response = $this->get('/dashboard');
        $response->assertRedirect('/login');
    }

    public function test_authenticated_user_can_view_dashboard_with_kpi_and_contracts(): void
    {
        $user = User::factory()->create([
            'role' => UserRole::AM,
            'is_active' => true,
        ]);

        $mockContractService = Mockery::mock(ContractService::class);
        $mockContractService->shouldReceive('getAllContracts')
            ->once()
            ->andReturn($this->getSampleContracts());

        $mockContractService->shouldReceive('getKpiSummary')
            ->once()
            ->andReturn([
                'total_pipeline_revenue' => 230000000,
                'total_pipeline_revenue_formatted' => 'Rp 230.000.000',
                'total_realized_revenue' => 155000000,
                'total_realized_revenue_formatted' => 'Rp 155.000.000',
                'realized_percentage' => 67.4,
                'total_lop' => 2,
                'kontrak_berjalan_count' => 2,
                'kontrak_selesai_count' => 0,
                'active_contracts' => 1,
                'expiring_soon_contracts' => 1,
                'overdue_contracts' => 0,
                'stages' => [],
                'invoice_counts' => [],
                'billcomp_counts' => [],
            ]);

        $mockContractService->shouldReceive('getContractsByStage')
            ->once()
            ->andReturn([
                'F0' => ['stage' => 'F0', 'label' => 'Lead', 'count' => 0, 'total_revenue' => 0, 'total_revenue_formatted' => 'Rp 0', 'contracts' => []],
                'F1' => ['stage' => 'F1', 'label' => 'Opportunity', 'count' => 0, 'total_revenue' => 0, 'total_revenue_formatted' => 'Rp 0', 'contracts' => []],
                'F2' => ['stage' => 'F2', 'label' => 'Quote', 'count' => 0, 'total_revenue' => 0, 'total_revenue_formatted' => 'Rp 0', 'contracts' => []],
                'F3' => ['stage' => 'F3', 'label' => 'Bidding', 'count' => 1, 'total_revenue' => 150000000, 'total_revenue_formatted' => 'Rp 150.000.000', 'contracts' => []],
                'F4' => ['stage' => 'F4', 'label' => 'Negotiation', 'count' => 1, 'total_revenue' => 80000000, 'total_revenue_formatted' => 'Rp 80.000.000', 'contracts' => []],
            ]);

        $this->app->instance(ContractService::class, $mockContractService);

        // Create a notification for the user
        Notification::factory()->create([
            'user_id' => $user->id,
            'lop_reference' => 'LOP-102',
            'alert_type' => 'EXPIRING_SOON',
            'days_remaining' => 15,
            'is_read' => false,
        ]);

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertOk();
        $response->assertViewIs('dashboard.index');
        $response->assertSee('Monitoring Kontrak Kerja B2B');
        $response->assertSee('Rp 230.000.000');
        $response->assertSee('Berjalan');
        $response->assertSee('Selesai');
        $response->assertSee('status_kontrak=BERJALAN');
        $response->assertSee('status_kontrak=SELESAI');
        $response->assertSee('LOP-101');
        $response->assertSee('LOP-102');
        $response->assertSee('Satker Jakarta');
        $response->assertSee('Satker Bandung');
    }

    public function test_user_can_force_refresh_dashboard(): void
    {
        $user = User::factory()->create([
            'role' => UserRole::AM,
            'is_active' => true,
        ]);

        $mockContractService = Mockery::mock(ContractService::class);
        $mockContractService->shouldReceive('getAllContracts')
            ->once()
            ->with(true)
            ->andReturn($this->getSampleContracts());

        $this->app->instance(ContractService::class, $mockContractService);

        $response = $this->actingAs($user)->post('/dashboard/refresh');

        $response->assertRedirect();
        $response->assertSessionHas('status');
    }

    public function test_user_can_mark_notification_as_read(): void
    {
        $user = User::factory()->create();
        $notification = Notification::factory()->create([
            'user_id' => $user->id,
            'is_read' => false,
        ]);

        $response = $this->actingAs($user)->postJson("/notifications/{$notification->id}/read");

        $response->assertOk();
        $response->assertJson(['success' => true]);
        $this->assertTrue($notification->fresh()->is_read);
    }

    public function test_user_can_mark_all_notifications_as_read(): void
    {
        $user = User::factory()->create();
        Notification::factory()->count(3)->create([
            'user_id' => $user->id,
            'is_read' => false,
        ]);

        $response = $this->actingAs($user)->postJson('/notifications/read-all');

        $response->assertOk();
        $response->assertJson(['success' => true, 'unread_count' => 0]);
        $this->assertEquals(0, Notification::where('user_id', $user->id)->unread()->count());
    }

    public function test_dashboard_excludes_completed_contracts_from_overdue_list_and_urgent_alerts(): void
    {
        $user = User::factory()->create([
            'role' => UserRole::AM,
            'is_active' => true,
        ]);

        $contracts = [
            [
                '_row_index' => 2,
                'lop' => 'LOP-ACTIVE-OVERDUE',
                'contract_number' => 'CTR/101',
                'customer' => 'PT Pelanggan Aktif',
                'satker' => 'Satker Aktif',
                'service' => 'Internet',
                'stage' => 'F4',
                'revenue' => 50000000,
                'start_date' => '2025-01-01',
                'end_date' => '2025-12-31',
                'days_remaining' => -60,
                'expiration_status' => 'OVERDUE',
                'is_overdue' => true,
                'status_kontrak' => 'BERJALAN',
            ],
            [
                '_row_index' => 3,
                'lop' => 'LOP-DONE-OVERDUE',
                'contract_number' => 'CTR/102',
                'customer' => 'PT Pelanggan Selesai',
                'satker' => 'Satker Selesai',
                'service' => 'Astinet',
                'stage' => 'F4',
                'revenue' => 75000000,
                'start_date' => '2025-01-01',
                'end_date' => '2025-12-31',
                'days_remaining' => -100,
                'expiration_status' => 'OVERDUE',
                'is_overdue' => false,
                'status_kontrak' => 'SELESAI',
                'is_completed' => true,
            ],
        ];

        $mockContractService = Mockery::mock(ContractService::class);
        $mockContractService->shouldReceive('getAllContracts')
            ->once()
            ->andReturn($contracts);

        $mockContractService->shouldReceive('getKpiSummary')
            ->once()
            ->with($contracts)
            ->andReturn([
                'total_pipeline_revenue' => 125000000,
                'total_pipeline_revenue_formatted' => 'Rp 125.000.000',
                'total_realized_revenue' => 0,
                'total_realized_revenue_formatted' => 'Rp 0',
                'realized_percentage' => 0,
                'total_lop' => 2,
                'active_contracts' => 0,
                'expiring_soon_contracts' => 0,
                'overdue_contracts' => 1,
                'stages' => [],
                'invoice_counts' => [],
                'billcomp_counts' => [],
            ]);

        $mockContractService->shouldReceive('getContractsByStage')
            ->once()
            ->andReturn([]);

        $this->app->instance(ContractService::class, $mockContractService);

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertOk();
        $overdueContracts = $response->viewData('overdueContracts');
        $this->assertCount(1, $overdueContracts);
        $this->assertEquals('LOP-ACTIVE-OVERDUE', $overdueContracts[0]['lop']);

        // Check that completed contract does not appear in urgent list
        $response->assertSee('LOP-ACTIVE-OVERDUE');
        $response->assertDontSee('OVERDUE (100h lalu)');
    }
}


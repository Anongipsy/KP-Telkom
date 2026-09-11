<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\ContractService;
use App\Services\GoogleSheetsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class AdvancedContractFiltersTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create(['role' => \App\Enums\UserRole::AM]);

        $mockContracts = [
            [
                '_row_index' => 2,
                'lop' => 'LOP-PAR-2026-001',
                'id_mytens' => 'MYT-001',
                'tahun' => '2026',
                'nama_gc' => 'Pemkot Pariaman',
                'satker' => 'Dinas Pariwisata',
                'customer' => 'Pemkot Pariaman',
                'judul_proyek' => 'Pengembangan Pariwisata Digital',
                'contract_number' => 'CTR/PAR/001',
                'service' => 'Astinet Dedicated 100 Mbps',
                'stage' => 'F0',
                'stage_label' => 'Lead',
                'revenue' => 50000000,
                'revenue_formatted' => 'Rp 50.000.000',
                'start_date' => '2026-01-01',
                'end_date' => \Carbon\Carbon::now()->addDays(90)->format('Y-m-d'),
                'days_remaining' => 90,
                'expiration_status' => 'ACTIVE',
                'sp_po' => 'AVAILABLE',
                'status_kontrak' => 'BERJALAN',
                'status_kontrak_label' => 'Berjalan',
                'billcomp_percentage' => 0,
                'nilai_bc' => 0,
                'nilai_bc_formatted' => 'Rp 0',
                'realized_revenue' => 0,
                'realized_revenue_formatted' => 'Rp 0',
                'document_reference' => null,
            ],
            [
                '_row_index' => 3,
                'lop' => 'LOP-PAR-2026-002',
                'id_mytens' => 'MYT-002',
                'tahun' => '2026',
                'nama_gc' => 'Pemkot Pariaman',
                'satker' => 'BPKPD',
                'customer' => 'Pemkot Pariaman',
                'judul_proyek' => 'Sistem Informasi Keuangan Daerah',
                'contract_number' => 'CTR/PAR/002',
                'service' => 'Metro Ethernet 200 Mbps',
                'stage' => 'F2',
                'stage_label' => 'Quote',
                'revenue' => 100000000,
                'revenue_formatted' => 'Rp 100.000.000',
                'start_date' => '2026-01-01',
                'end_date' => \Carbon\Carbon::now()->subDays(10)->format('Y-m-d'),
                'days_remaining' => -10,
                'expiration_status' => 'OVERDUE',
                'sp_po' => 'MISSING',
                'status_kontrak' => 'BERJALAN',
                'status_kontrak_label' => 'Berjalan',
                'billcomp_percentage' => 20,
                'nilai_bc' => 20000000,
                'nilai_bc_formatted' => 'Rp 20.000.000',
                'realized_revenue' => 20000000,
                'realized_revenue_formatted' => 'Rp 20.000.000',
                'document_reference' => null,
            ],
            [
                '_row_index' => 4,
                'lop' => 'LOP-PAR-2026-003',
                'id_mytens' => 'MYT-003',
                'tahun' => '2026',
                'nama_gc' => 'Pemkot Pariaman',
                'satker' => 'Diskominfo Kota Pariaman',
                'customer' => 'Diskominfo Kota Pariaman',
                'judul_proyek' => 'Penyediaan Internet OPD Kota Pariaman',
                'contract_number' => 'CTR/PAR/003',
                'service' => 'Astinet 500 Mbps',
                'stage' => 'F3',
                'stage_label' => 'Bidding',
                'revenue' => 150000000,
                'revenue_formatted' => 'Rp 150.000.000',
                'start_date' => '2026-01-01',
                'end_date' => \Carbon\Carbon::now()->addDays(90)->format('Y-m-d'),
                'days_remaining' => 90,
                'expiration_status' => 'ACTIVE',
                'sp_po' => 'AVAILABLE',
                'status_kontrak' => 'BERJALAN',
                'status_kontrak_label' => 'Berjalan',
                'billcomp_percentage' => 50,
                'nilai_bc' => 75000000,
                'nilai_bc_formatted' => 'Rp 75.000.000',
                'realized_revenue' => 75000000,
                'realized_revenue_formatted' => 'Rp 75.000.000',
                'document_reference' => null,
            ],
            [
                '_row_index' => 5,
                'lop' => 'LOP-PAS-2025-006',
                'id_mytens' => 'MYT-004',
                'tahun' => '2025',
                'nama_gc' => 'Pemkab Pasaman',
                'satker' => 'Dinas Kesehatan Pasaman',
                'customer' => 'Pemkab Pasaman',
                'judul_proyek' => 'Jaringan Puskesmas Pasaman',
                'contract_number' => 'CTR/PAS/006',
                'service' => 'VPN IP 50 Mbps',
                'stage' => 'F4',
                'stage_label' => 'Negotiation',
                'revenue' => 200000000,
                'revenue_formatted' => 'Rp 200.000.000',
                'start_date' => '2025-01-01',
                'end_date' => \Carbon\Carbon::now()->addDays(120)->format('Y-m-d'),
                'days_remaining' => 120,
                'expiration_status' => 'ACTIVE',
                'sp_po' => 'AVAILABLE',
                'status_kontrak' => 'BERJALAN',
                'status_kontrak_label' => 'Berjalan',
                'billcomp_percentage' => 10,
                'nilai_bc' => 20000000,
                'nilai_bc_formatted' => 'Rp 20.000.000',
                'realized_revenue' => 20000000,
                'realized_revenue_formatted' => 'Rp 20.000.000',
                'document_reference' => null,
            ],
        ];

        $mockSheets = Mockery::mock(GoogleSheetsService::class);
        $mockSheets->shouldReceive('getAllContracts')->andReturn($mockContracts);
        $this->app->instance(GoogleSheetsService::class, $mockSheets);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /**
     * Test submitting all empty filter parameters (default state) does not crash or falsely show reset button.
     */
    public function test_empty_filter_parameters_returns_all_contracts(): void
    {
        $response = $this->actingAs($this->user)->get('/contracts?view=table&q=&tahun=&nama_gc=&stage=&satker=&expiration_status=&sp_po=');

        $response->assertStatus(200);
        $response->assertViewHas('activeFilterCount', 0);
        $response->assertViewHas('contracts');
    }

    /**
     * Test filter by year.
     */
    public function test_filter_by_year(): void
    {
        $response = $this->actingAs($this->user)->get('/contracts?tahun=2026');

        $response->assertStatus(200);
        $response->assertViewHas('activeFilterCount', 1);
        $contracts = $response->viewData('contracts');

        foreach ($contracts as $contract) {
            $this->assertEquals('2026', (string) $contract['tahun']);
        }
    }

    /**
     * Test filter by Nama GC.
     */
    public function test_filter_by_nama_gc(): void
    {
        $response = $this->actingAs($this->user)->get('/contracts?nama_gc=' . urlencode('Pemkot Pariaman'));

        $response->assertStatus(200);
        $response->assertViewHas('activeFilterCount', 1);
        $contracts = $response->viewData('contracts');

        $this->assertNotEmpty($contracts);
        foreach ($contracts as $contract) {
            $this->assertEquals('Pemkot Pariaman', $contract['nama_gc']);
        }
    }

    /**
     * Test filter by Stage.
     */
    public function test_filter_by_stage(): void
    {
        $response = $this->actingAs($this->user)->get('/contracts?stage=F3');

        $response->assertStatus(200);
        $response->assertViewHas('activeFilterCount', 1);
        $contracts = $response->viewData('contracts');

        $this->assertNotEmpty($contracts);
        foreach ($contracts as $contract) {
            $this->assertEquals('F3', $contract['stage']);
        }
    }

    /**
     * Test filter by Satker (exact and partial).
     */
    public function test_filter_by_satker(): void
    {
        $response = $this->actingAs($this->user)->get('/contracts?satker=' . urlencode('Diskominfo Kota Pariaman'));

        $response->assertStatus(200);
        $contracts = $response->viewData('contracts');

        $this->assertCount(1, $contracts);
        $this->assertEquals('Diskominfo Kota Pariaman', $contracts[0]['satker']);
    }

    /**
     * Test filter by Expiration Status.
     */
    public function test_filter_by_expiration_status(): void
    {
        $response = $this->actingAs($this->user)->get('/contracts?expiration_status=OVERDUE');

        $response->assertStatus(200);
        $contracts = $response->viewData('contracts');
        $this->assertNotEmpty($contracts);
        foreach ($contracts as $contract) {
            $this->assertEquals('OVERDUE', $contract['expiration_status']);
        }
    }

    /**
     * Test filter by SP/PO status.
     */
    public function test_filter_by_sp_po(): void
    {
        $response = $this->actingAs($this->user)->get('/contracts?sp_po=MISSING');

        $response->assertStatus(200);
        $contracts = $response->viewData('contracts');

        $this->assertNotEmpty($contracts);
        foreach ($contracts as $contract) {
            $this->assertEquals('MISSING', $contract['sp_po']);
        }
    }

    /**
     * Test combination of multiple filters.
     */
    public function test_combination_of_filters(): void
    {
        $response = $this->actingAs($this->user)->get('/contracts?nama_gc=' . urlencode('Pemkab Pasaman') . '&stage=F4');

        $response->assertStatus(200);
        $response->assertViewHas('activeFilterCount', 2);
        $contracts = $response->viewData('contracts');

        $this->assertCount(1, $contracts);
        $this->assertEquals('Pemkab Pasaman', $contracts[0]['nama_gc']);
        $this->assertEquals('F4', $contracts[0]['stage']);
        $this->assertEquals('LOP-PAS-2025-006', $contracts[0]['lop']);
    }

    /**
     * Test Kanban stages reflect the filtered contracts instead of always showing all contracts.
     */
    public function test_kanban_stages_reflect_active_filters(): void
    {
        $response = $this->actingAs($this->user)->get('/contracts?view=kanban&nama_gc=' . urlencode('Pemkot Pariaman'));

        $response->assertStatus(200);
        $stages = $response->viewData('stages');

        // Total count across all stages must equal filtered contracts count (3 for Pemkot Pariaman)
        $totalInKanban = 0;
        foreach ($stages as $stageData) {
            $totalInKanban += $stageData['count'];
        }

        $this->assertEquals(3, $totalInKanban);
        $this->assertEquals(1, $stages['F0']['count']); // Dinas Pariwisata
        $this->assertEquals(0, $stages['F1']['count']);
        $this->assertEquals(1, $stages['F2']['count']); // BPKPD
        $this->assertEquals(1, $stages['F3']['count']); // Diskominfo
        $this->assertEquals(0, $stages['F4']['count']);
    }
}

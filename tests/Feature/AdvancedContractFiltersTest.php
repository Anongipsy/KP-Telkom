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

<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\User;
use App\Services\ContractService;
use App\Services\DataTransformer;
use App\Services\ExpirationService;
use App\Services\GoogleSheetsService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Mockery\MockInterface;
use Tests\TestCase;

class ContractServiceTest extends TestCase
{
    use RefreshDatabase;

    protected ContractService $contractService;
    protected MockInterface $mockSheetsService;
    protected ExpirationService $expirationService;
    protected DataTransformer $transformer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->mockSheetsService = Mockery::mock(GoogleSheetsService::class);
        $this->expirationService = new ExpirationService();
        $this->transformer = new DataTransformer();

        $this->contractService = new ContractService(
            $this->mockSheetsService,
            $this->expirationService,
            $this->transformer
        );
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    protected function getSampleTransformedContracts(): array
    {
        return [
            [
                '_row_index' => 2,
                'lop' => 'LOP-001',
                'contract_number' => 'CTR/001',
                'customer' => 'PT Satu',
                'satker' => 'Satker A',
                'service' => 'Astinet',
                'stage' => 'F3',
                'stage_label' => 'Bidding',
                'revenue' => 100000000,
                'start_date' => '2026-01-01',
                'end_date' => Carbon::now()->addDays(90)->format('Y-m-d'), // Active (>60)
                'sp_po' => 'AVAILABLE',
                'invoice_status' => 'ISSUED',
                'billcomp_status' => 'PARTIAL',
                'billcomp_percentage' => 50,
                'realized_revenue' => 50000000,
                'document_reference' => 'ref1',
            ],
            [
                '_row_index' => 3,
                'lop' => 'LOP-002',
                'contract_number' => 'CTR/002',
                'customer' => 'PT Dua',
                'satker' => 'Satker B',
                'service' => 'Indibiz',
                'stage' => 'F4',
                'stage_label' => 'Negotiation',
                'revenue' => 50000000,
                'start_date' => '2026-02-01',
                'end_date' => Carbon::now()->addDays(20)->format('Y-m-d'), // Expiring soon (0-60)
                'sp_po' => 'AVAILABLE',
                'invoice_status' => 'PAID',
                'billcomp_status' => 'COMPLETED',
                'billcomp_percentage' => 100,
                'realized_revenue' => 50000000,
                'document_reference' => 'ref2',
            ],
            [
                '_row_index' => 4,
                'lop' => 'LOP-003',
                'contract_number' => 'CTR/003',
                'customer' => 'PT Tiga',
                'satker' => 'Satker C',
                'service' => 'Cloud',
                'stage' => 'F0',
                'stage_label' => 'Lead',
                'revenue' => 20000000,
                'start_date' => '2026-01-01',
                'end_date' => Carbon::now()->subDays(10)->format('Y-m-d'), // Overdue (<0)
                'sp_po' => 'MISSING',
                'invoice_status' => 'UNBILLED',
                'billcomp_status' => 'NOT_COMPLETE',
                'billcomp_percentage' => 0,
                'realized_revenue' => 0,
                'document_reference' => '',
            ],
        ];
    }

    public function test_get_all_contracts_returns_enriched_data(): void
    {
        $this->mockSheetsService
            ->shouldReceive('getAllContracts')
            ->once()
            ->with(false)
            ->andReturn($this->getSampleTransformedContracts());

        $contracts = $this->contractService->getAllContracts();

        $this->assertCount(3, $contracts);
        $this->assertEquals('ACTIVE', $contracts[0]['expiration_status']);
        $this->assertEquals('EXPIRING_SOON', $contracts[1]['expiration_status']);
        $this->assertEquals('OVERDUE', $contracts[2]['expiration_status']);
    }

    public function test_find_by_lop(): void
    {
        $this->mockSheetsService
            ->shouldReceive('findByLop')
            ->once()
            ->with('LOP-001')
            ->andReturn($this->getSampleTransformedContracts()[0]);

        $contract = $this->contractService->findByLop('LOP-001');

        $this->assertNotNull($contract);
        $this->assertEquals('LOP-001', $contract['lop']);
        $this->assertEquals('ACTIVE', $contract['expiration_status']);
    }

    public function test_create_contract_and_audit_log(): void
    {
        $user = User::factory()->create();

        $newContractInput = [
            'lop' => 'LOP-NEW',
            'contract_number' => 'CTR/NEW',
            'customer' => 'New Customer Co',
            'service' => 'Metro-E',
            'stage' => 'F1',
            'revenue' => 'Rp 80.000.000',
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
        ];

        $appendedReturn = [
            '_row_index' => 5,
            'lop' => 'LOP-NEW',
            'contract_number' => 'CTR/NEW',
            'customer' => 'New Customer Co',
            'service' => 'Metro-E',
            'stage' => 'F1',
            'revenue' => 80000000,
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
        ];

        $this->mockSheetsService
            ->shouldReceive('appendContract')
            ->once()
            ->andReturn($appendedReturn);

        $result = $this->contractService->createContract($newContractInput, $user->id);

        $this->assertEquals('LOP-NEW', $result['lop']);

        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $user->id,
            'action' => 'contract_create',
            'target_type' => 'contract',
            'target_reference' => 'LOP-NEW',
        ]);
    }

    public function test_update_contract_and_audit_log(): void
    {
        $user = User::factory()->create();

        $this->mockSheetsService
            ->shouldReceive('findByLop')
            ->once()
            ->with('LOP-001')
            ->andReturn($this->getSampleTransformedContracts()[0]);

        $updatedReturn = array_merge($this->getSampleTransformedContracts()[0], [
            'stage' => 'F4',
            'revenue' => 120000000,
        ]);

        $this->mockSheetsService
            ->shouldReceive('updateByLop')
            ->once()
            ->andReturn($updatedReturn);

        $result = $this->contractService->updateContract('LOP-001', [
            'customer' => 'PT Satu',
            'service' => 'Astinet',
            'stage' => 'F4',
            'revenue' => 120000000,
        ], $user->id);

        $this->assertEquals('F4', $result['stage']);

        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $user->id,
            'action' => 'contract_update',
            'target_type' => 'contract',
            'target_reference' => 'LOP-001',
        ]);
    }

    public function test_get_kpi_summary(): void
    {
        $this->mockSheetsService
            ->shouldReceive('getAllContracts')
            ->once()
            ->andReturn($this->getSampleTransformedContracts());

        $kpi = $this->contractService->getKpiSummary();

        // Total revenue: 100M + 50M + 20M = 170M
        $this->assertEquals(170000000, $kpi['total_pipeline_revenue']);
        $this->assertEquals('Rp 170.000.000', $kpi['total_pipeline_revenue_formatted']);

        // Realized revenue: 50M + 50M + 0 = 100M
        $this->assertEquals(100000000, $kpi['total_realized_revenue']);

        $this->assertEquals(3, $kpi['total_lop']);
        $this->assertEquals(3, $kpi['kontrak_berjalan_count']);
        $this->assertEquals(0, $kpi['kontrak_selesai_count']);
        $this->assertEquals(1, $kpi['active_contracts']);
        $this->assertEquals(1, $kpi['expiring_soon_contracts']);
        $this->assertEquals(1, $kpi['overdue_contracts']);

        // Stage breakdown
        $this->assertEquals(1, $kpi['stages']['F0']['count']);
        $this->assertEquals(1, $kpi['stages']['F3']['count']);
        $this->assertEquals(1, $kpi['stages']['F4']['count']);
        $this->assertEquals(0, $kpi['stages']['F1']['count']);
    }

    public function test_get_kpi_summary_excludes_completed_contracts_from_overdue(): void
    {
        $contracts = [
            [
                'lop' => 'LOP-001',
                'revenue' => 10000000,
                'realized_revenue' => 10000000,
                'is_overdue' => true,
                'status_kontrak' => 'SELESAI',
                'is_completed' => true,
            ],
            [
                'lop' => 'LOP-002',
                'revenue' => 20000000,
                'realized_revenue' => 0,
                'is_overdue' => true,
                'status_kontrak' => 'BERJALAN',
                'is_completed' => false,
            ],
        ];

        $kpi = $this->contractService->getKpiSummary($contracts);
        $this->assertEquals(1, $kpi['overdue_contracts']);
        $this->assertEquals(1, $kpi['kontrak_berjalan_count']);
        $this->assertEquals(1, $kpi['kontrak_selesai_count']);
    }

    public function test_search_contracts_by_customer_and_lop(): void
    {
        $contracts = $this->contractService->getAllContractsFromSample = $this->expirationService->enrichAll($this->getSampleTransformedContracts());

        // Search by customer name
        $results = $this->contractService->searchContracts('PT Satu', $contracts);
        $this->assertCount(1, $results);
        $this->assertEquals('LOP-001', $results[0]['lop']);

        // Search by LOP (case-insensitive)
        $results = $this->contractService->searchContracts('lop-002', $contracts);
        $this->assertCount(1, $results);
        $this->assertEquals('PT Dua', $results[0]['customer']);

        // Search by Service
        $results = $this->contractService->searchContracts('Astinet', $contracts);
        $this->assertCount(1, $results);

        // Search with no match
        $results = $this->contractService->searchContracts('NonExistentKeyword', $contracts);
        $this->assertCount(0, $results);

        // Empty search returns all
        $results = $this->contractService->searchContracts('', $contracts);
        $this->assertCount(3, $results);
    }

    public function test_filter_by_stage_and_multiple_stages(): void
    {
        $contracts = $this->expirationService->enrichAll($this->getSampleTransformedContracts());

        // Single stage
        $f3Results = $this->contractService->filterContracts(['stage' => 'F3'], $contracts);
        $this->assertCount(1, $f3Results);
        $this->assertEquals('LOP-001', $f3Results[0]['lop']);

        // Multiple stages
        $multiResults = $this->contractService->filterContracts(['stage' => ['F0', 'F4']], $contracts);
        $this->assertCount(2, $multiResults);
    }

    public function test_filter_by_expiration_status(): void
    {
        $contracts = $this->expirationService->enrichAll($this->getSampleTransformedContracts());

        // Filter ACTIVE
        $active = $this->contractService->filterContracts(['expiration_status' => 'ACTIVE'], $contracts);
        $this->assertCount(1, $active);
        $this->assertEquals('LOP-001', $active[0]['lop']);

        // Filter EXPIRING_SOON
        $expiring = $this->contractService->filterContracts(['expiration_status' => 'EXPIRING_SOON'], $contracts);
        $this->assertCount(1, $expiring);
        $this->assertEquals('LOP-002', $expiring[0]['lop']);

        // Filter OVERDUE
        $overdue = $this->contractService->filterContracts(['expiration_status' => 'OVERDUE'], $contracts);
        $this->assertCount(1, $overdue);
        $this->assertEquals('LOP-003', $overdue[0]['lop']);
    }

    public function test_filter_by_revenue_range(): void
    {
        $contracts = $this->expirationService->enrichAll($this->getSampleTransformedContracts());

        $filtered = $this->contractService->filterContracts([
            'revenue_min' => 40000000,
            'revenue_max' => 110000000,
        ], $contracts);

        // Should include 50M (LOP-002) and 100M (LOP-001), exclude 20M (LOP-003)
        $this->assertCount(2, $filtered);
    }

    public function test_combined_search_and_filter(): void
    {
        $contracts = $this->expirationService->enrichAll($this->getSampleTransformedContracts());

        // Search 'PT' and filter stage 'F4'
        $results = $this->contractService->searchAndFilter('PT', ['stage' => 'F4'], $contracts);

        $this->assertCount(1, $results);
        $this->assertEquals('LOP-002', $results[0]['lop']);
    }

    public function test_get_contracts_by_stage_groups_correctly(): void
    {
        $contracts = $this->expirationService->enrichAll($this->getSampleTransformedContracts());

        $stages = $this->contractService->getContractsByStage($contracts);

        $this->assertArrayHasKey('F0', $stages);
        $this->assertArrayHasKey('F1', $stages);
        $this->assertArrayHasKey('F2', $stages);
        $this->assertArrayHasKey('F3', $stages);
        $this->assertArrayHasKey('F4', $stages);

        $this->assertEquals(1, $stages['F0']['count']);
        $this->assertEquals(20000000, $stages['F0']['total_revenue']);

        $this->assertEquals(0, $stages['F1']['count']);
        $this->assertEquals(0, $stages['F1']['total_revenue']);

        $this->assertEquals(1, $stages['F3']['count']);
        $this->assertEquals(100000000, $stages['F3']['total_revenue']);

        $this->assertEquals(1, $stages['F4']['count']);
        $this->assertEquals(50000000, $stages['F4']['total_revenue']);
    }

    public function test_sort_contracts(): void
    {
        $contracts = $this->expirationService->enrichAll($this->getSampleTransformedContracts());

        // Sort by revenue DESC (100M, 50M, 20M)
        $sortedDesc = $this->contractService->sortContracts($contracts, 'revenue', 'desc');
        $this->assertEquals('LOP-001', $sortedDesc[0]['lop']);
        $this->assertEquals('LOP-002', $sortedDesc[1]['lop']);
        $this->assertEquals('LOP-003', $sortedDesc[2]['lop']);

        // Sort by revenue ASC (20M, 50M, 100M)
        $sortedAsc = $this->contractService->sortContracts($contracts, 'revenue', 'asc');
        $this->assertEquals('LOP-003', $sortedAsc[0]['lop']);
        $this->assertEquals('LOP-002', $sortedAsc[1]['lop']);
        $this->assertEquals('LOP-001', $sortedAsc[2]['lop']);
    }

    public function test_create_contract_auto_calculates_billcomp_percentage_from_nominal(): void
    {
        $user = User::factory()->create();

        $newContractInput = [
            'lop' => 'LOP-BC-001',
            'contract_number' => 'CTR/BC/001',
            'customer' => 'Customer BC',
            'service' => 'Astinet',
            'stage' => 'F2',
            'revenue' => 'Rp 200.000.000',
            'billcomp_nominal' => 'Rp 50.000.000',
            'billcomp_status' => 'PARTIAL',
        ];

        $this->mockSheetsService
            ->shouldReceive('appendContract')
            ->once()
            ->with(\Mockery::on(function ($arg) {
                return isset($arg['billcomp_percentage']) && $arg['billcomp_percentage'] === 25;
            }), $user->id)
            ->andReturn([
                '_row_index' => 10,
                'lop' => 'LOP-BC-001',
                'customer' => 'Customer BC',
                'service' => 'Astinet',
                'stage' => 'F2',
                'revenue' => 200000000,
                'start_date' => '2026-01-01',
                'end_date' => '2026-12-31',
                'sp_po' => 'AVAILABLE',
                'invoice_status' => 'UNBILLED',
                'billcomp_status' => 'PARTIAL',
                'billcomp_percentage' => 25,
            ]);

        $result = $this->contractService->createContract($newContractInput, $user->id);

        $this->assertNotNull($result);
        $this->assertEquals(25, $result['billcomp_percentage']);
    }

    public function test_service_field_accepts_up_to_700_characters(): void
    {
        $user = User::factory()->create();
        $longServiceDescription = str_repeat('A', 700);

        $input = [
            'lop' => 'LOP-700-CHARS',
            'customer' => 'Customer 700',
            'service' => $longServiceDescription,
            'stage' => 'F1',
            'revenue' => 100000000,
        ];

        $this->mockSheetsService
            ->shouldReceive('appendContract')
            ->once()
            ->with(\Mockery::on(function ($arg) use ($longServiceDescription) {
                return $arg['service'] === $longServiceDescription;
            }), $user->id)
            ->andReturn([
                '_row_index' => 11,
                'lop' => 'LOP-700-CHARS',
                'customer' => 'Customer 700',
                'service' => $longServiceDescription,
                'stage' => 'F1',
                'revenue' => 100000000,
            ]);

        $created = $this->contractService->createContract($input, $user->id);
        $this->assertEquals(700, strlen($created['service']));
    }

    public function test_service_field_rejects_exceeding_700_characters(): void
    {
        $this->expectException(\Illuminate\Validation\ValidationException::class);

        $input = [
            'lop' => 'LOP-701-CHARS',
            'customer' => 'Customer 701',
            'service' => str_repeat('A', 701),
            'stage' => 'F1',
            'revenue' => 100000000,
        ];

        $this->contractService->createContract($input);
    }
}

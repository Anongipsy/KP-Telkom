<?php

namespace Tests\Feature;

use App\Models\SyncLog;
use App\Models\User;
use App\Services\DataTransformer;
use App\Services\GoogleSheetsService;
use Google\Service\Sheets as GoogleSheets;
use Google\Service\Sheets\AppendValuesResponse;
use Google\Service\Sheets\Resource\SpreadsheetsValues;
use Google\Service\Sheets\UpdateValuesResponse;
use Google\Service\Sheets\ValueRange;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Mockery;
use Mockery\MockInterface;
use RuntimeException;
use Tests\TestCase;

class GoogleSheetsServiceTest extends TestCase
{
    use RefreshDatabase;

    protected GoogleSheetsService $service;
    protected MockInterface $mockValuesResource;
    protected MockInterface $mockSheetsService;

    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();

        config([
            'google.sheets.spreadsheet_id' => 'test-spreadsheet-id-12345',
            'google.sheets.sheet_name' => 'Contracts',
            'google.sheets.range' => 'Contracts!A:U',
            'google.sheets.header_row' => 1,
            'google.sheets.cache_ttl' => 300,
        ]);

        $this->mockSheetsService = Mockery::mock(GoogleSheets::class);
        $this->mockValuesResource = Mockery::mock(SpreadsheetsValues::class);
        $this->mockSheetsService->spreadsheets_values = $this->mockValuesResource;

        $this->service = new GoogleSheetsService(new DataTransformer());
        $this->service->setSheetsService($this->mockSheetsService);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    protected function createSampleSpreadsheetValues(): ValueRange
    {
        $response = new ValueRange();
        $response->setValues([
            DataTransformer::STANDARD_HEADERS,
            [
                'TENS-001',
                '2026',
                'LOP-001',
                'CTR/001',
                'PT Telkom Group',
                'Satker Alpha',
                'PT Customer Satu',
                'Astinet 100 Mbps',
                'F3',
                'Rp 120.000.000',
                'Rp 100.000.000',
                '2026-01-01',
                '2026-12-31',
                '12',
                'Desember 2026',
                'Rp 8.333.333',
                'AVAILABLE',
                'ISSUED',
                'PARTIAL',
                '50%',
                'doc_ref_1',
            ],
            [
                'TENS-002',
                '2026',
                'LOP-002',
                'CTR/002',
                'PT Telkom Group',
                'Satker Beta',
                'PT Customer Dua',
                'Indibiz 50 Mbps',
                'F4',
                'Rp 60.000.000',
                'Rp 50.000.000',
                '2026-02-01',
                '2026-09-30',
                '8',
                'September 2026',
                'Rp 6.250.000',
                'AVAILABLE',
                'PAID',
                'COMPLETED',
                '100%',
                'doc_ref_2',
            ],
        ]);
        return $response;
    }

    public function test_get_all_contracts_reads_and_transforms_rows(): void
    {
        $this->mockValuesResource
            ->shouldReceive('get')
            ->once()
            ->with('test-spreadsheet-id-12345', 'Contracts!A:U', [
                'valueRenderOption' => 'UNFORMATTED_VALUE',
                'dateTimeRenderOption' => 'SERIAL_NUMBER',
            ])
            ->andReturn($this->createSampleSpreadsheetValues());

        $contracts = $this->service->getAllContracts();

        $this->assertCount(2, $contracts);
        $this->assertEquals('LOP-001', $contracts[0]['lop']);
        $this->assertEquals(100000000, $contracts[0]['revenue']);
        $this->assertEquals('PT Customer Satu', $contracts[0]['customer']);
        $this->assertEquals('F3', $contracts[0]['stage']);

        $this->assertEquals('LOP-002', $contracts[1]['lop']);
        $this->assertEquals(50000000, $contracts[1]['revenue']);

        // Check sync log created
        $this->assertDatabaseHas('sync_logs', [
            'sync_type' => 'contracts',
            'direction' => 'SHEETS_TO_APP',
            'status' => 'success',
            'records_processed' => 2,
        ]);
    }

    public function test_get_all_contracts_uses_cache_on_subsequent_calls(): void
    {
        // Should only call Google Sheets API once due to caching
        $this->mockValuesResource
            ->shouldReceive('get')
            ->once()
            ->with('test-spreadsheet-id-12345', 'Contracts!A:U', [
                'valueRenderOption' => 'UNFORMATTED_VALUE',
                'dateTimeRenderOption' => 'SERIAL_NUMBER',
            ])
            ->andReturn($this->createSampleSpreadsheetValues());

        $firstCall = $this->service->getAllContracts();
        $secondCall = $this->service->getAllContracts();

        $this->assertEquals($firstCall, $secondCall);
        $this->assertTrue(Cache::has(GoogleSheetsService::CACHE_KEY_ALL_CONTRACTS));
    }

    public function test_find_by_lop(): void
    {
        $this->mockValuesResource
            ->shouldReceive('get')
            ->once()
            ->andReturn($this->createSampleSpreadsheetValues());

        $found = $this->service->findByLop('LOP-001');
        $this->assertNotNull($found);
        $this->assertEquals('LOP-001', $found['lop']);
        $this->assertEquals('PT Customer Satu', $found['customer']);

        $notFound = $this->service->findByLop('NON-EXISTENT');
        $this->assertNull($notFound);
    }

    public function test_update_by_lop(): void
    {
        $user = User::factory()->create();

        // 1. Initial read for findByLop
        $this->mockValuesResource
            ->shouldReceive('get')
            ->once()
            ->andReturn($this->createSampleSpreadsheetValues());

        // 2. Expect Sheets update call
        $this->mockValuesResource
            ->shouldReceive('update')
            ->once()
            ->withArgs(function ($spreadsheetId, $range, $valueRange, $optParams) {
                return $spreadsheetId === 'test-spreadsheet-id-12345'
                    && str_contains($range, '2') // Row 2 for LOP-001
                    && $optParams['valueInputOption'] === 'USER_ENTERED';
            })
            ->andReturn(new UpdateValuesResponse());

        $updated = $this->service->updateByLop('LOP-001', [
            'revenue' => 150000000,
            'stage' => 'F4',
        ], $user->id);

        $this->assertEquals('LOP-001', $updated['lop']);
        $this->assertEquals(150000000, $updated['revenue']);
        $this->assertEquals('F4', $updated['stage']);

        // Check cache cleared
        $this->assertFalse(Cache::has(GoogleSheetsService::CACHE_KEY_ALL_CONTRACTS));

        // Check sync log created
        $this->assertDatabaseHas('sync_logs', [
            'user_id' => $user->id,
            'sync_type' => 'contracts',
            'direction' => 'APP_TO_SHEETS',
            'status' => 'success',
            'records_processed' => 1,
        ]);
    }

    public function test_append_contract(): void
    {
        $user = User::factory()->create();

        // Initial read for duplicate check
        $this->mockValuesResource
            ->shouldReceive('get')
            ->once()
            ->andReturn($this->createSampleSpreadsheetValues());

        // Append call
        $this->mockValuesResource
            ->shouldReceive('append')
            ->once()
            ->withArgs(function ($spreadsheetId, $range, $valueRange, $optParams) {
                return $spreadsheetId === 'test-spreadsheet-id-12345'
                    && $optParams['valueInputOption'] === 'USER_ENTERED';
            })
            ->andReturn(new AppendValuesResponse());

        $newContract = [
            'lop' => 'LOP-003',
            'contract_number' => 'CTR/003',
            'customer' => 'PT Customer Tiga',
            'satker' => 'Satker Gamma',
            'service' => 'SD-WAN',
            'stage' => 'F1',
            'revenue' => 75000000,
        ];

        $created = $this->service->appendContract($newContract, $user->id);

        $this->assertEquals('LOP-003', $created['lop']);
        $this->assertEquals('PT Customer Tiga', $created['customer']);
        $this->assertEquals(75000000, $created['revenue']);

        $this->assertDatabaseHas('sync_logs', [
            'user_id' => $user->id,
            'sync_type' => 'contracts',
            'direction' => 'APP_TO_SHEETS',
            'status' => 'success',
        ]);
    }

    public function test_append_contract_throws_exception_on_duplicate_lop(): void
    {
        $this->mockValuesResource
            ->shouldReceive('get')
            ->once()
            ->andReturn($this->createSampleSpreadsheetValues());

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage("already exists");

        $this->service->appendContract([
            'lop' => 'LOP-001', // Already in sample data
            'customer' => 'Duplicate Co',
        ]);
    }

    public function test_api_error_handling_and_sync_log(): void
    {
        $this->mockValuesResource
            ->shouldReceive('get')
            ->once()
            ->andThrow(new \Exception('Google API 503 Service Unavailable'));

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Unable to retrieve contract data from Google Sheets');

        $this->service->getAllContracts();
    }

    public function test_serial_date_numbers_are_correctly_parsed_into_precise_dates(): void
    {
        // 46048 corresponds to 2026-01-26, 46387 corresponds to 2026-12-31
        $response = new ValueRange();
        $response->setValues([
            DataTransformer::STANDARD_HEADERS,
            [
                'TENS-001',
                '2026',
                'LOP-SERIAL-1',
                'CTR/001',
                'PT Telkom Group',
                'Satker Alpha',
                'PT Customer Satu',
                'Astinet 100 Mbps',
                'F3',
                3048648650,
                100000000,
                46048, // 2026-01-26
                46387, // 2026-12-31 (NOT reverted to 2026-12-01!)
                12,
                'Desember 2026',
                8333333,
                'AVAILABLE',
                'ISSUED',
                'PARTIAL',
                50,
                'doc_ref_1',
                'Kontrak Berjalan',
            ],
        ]);

        $this->mockValuesResource
            ->shouldReceive('get')
            ->once()
            ->andReturn($response);

        $contracts = $this->service->getAllContracts();

        $this->assertCount(1, $contracts);
        $this->assertEquals('2026-01-26', $contracts[0]['start_date']);
        $this->assertEquals('2026-12-31', $contracts[0]['end_date']);
        $this->assertNotEquals('2026-12-01', $contracts[0]['end_date']);
    }
}

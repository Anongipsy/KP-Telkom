<?php

namespace Tests\Unit;

use App\Enums\BillcompStatus;
use App\Enums\InvoiceStatus;
use App\Enums\Stage;
use App\Services\DataTransformer;
use PHPUnit\Framework\TestCase;

class DataTransformerTest extends TestCase
{
    protected DataTransformer $transformer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->transformer = new DataTransformer();
    }

    // --- Currency Parsing Tests ---

    public function test_parse_currency_with_various_formats(): void
    {
        $this->assertEquals(150000000, $this->transformer->parseCurrency('Rp 150.000.000'));
        $this->assertEquals(150000000, $this->transformer->parseCurrency('Rp. 150.000.000,00'));
        $this->assertEquals(150000000, $this->transformer->parseCurrency('150.000.000'));
        $this->assertEquals(150000000, $this->transformer->parseCurrency('150000000'));
        $this->assertEquals(150000000, $this->transformer->parseCurrency(150000000));
        $this->assertEquals(5000000, $this->transformer->parseCurrency('Rp 5.000.000'));
        $this->assertEquals(0, $this->transformer->parseCurrency(''));
        $this->assertEquals(0, $this->transformer->parseCurrency(null));
        $this->assertEquals(0, $this->transformer->parseCurrency('Rp -'));
    }

    public function test_format_currency(): void
    {
        $this->assertEquals('Rp 150.000.000', $this->transformer->formatCurrency(150000000));
        $this->assertEquals('Rp 0', $this->transformer->formatCurrency(0));
        $this->assertEquals('Rp 0', $this->transformer->formatCurrency(null));
    }

    // --- Date Normalization Tests ---

    public function test_parse_date_with_various_formats(): void
    {
        $this->assertEquals('2026-08-28', $this->transformer->parseDate('2026-08-28'));
        $this->assertEquals('2026-08-28', $this->transformer->parseDate('28/08/2026'));
        $this->assertEquals('2026-08-28', $this->transformer->parseDate('28-08-2026'));
        $this->assertEquals('2026-08-28', $this->transformer->parseDate('2026/08/28'));
        $this->assertEquals('2026-08-28', $this->transformer->parseDate('28 Aug 2026'));
        $this->assertNull($this->transformer->parseDate(''));
        $this->assertNull($this->transformer->parseDate(null));
        $this->assertNull($this->transformer->parseDate('invalid-date-string'));
    }

    // --- Status Normalization Tests ---

    public function test_normalize_stage(): void
    {
        $this->assertEquals(Stage::F0, $this->transformer->normalizeStage('F0'));
        $this->assertEquals(Stage::F1, $this->transformer->normalizeStage('f1'));
        $this->assertEquals(Stage::F2, $this->transformer->normalizeStage('F2 Quote'));
        $this->assertEquals(Stage::F3, $this->transformer->normalizeStage('Bidding'));
        $this->assertEquals(Stage::F4, $this->transformer->normalizeStage('Negotiation'));
        $this->assertEquals(Stage::F0, $this->transformer->normalizeStage(null));
        $this->assertEquals(Stage::F0, $this->transformer->normalizeStage('unknown'));
    }

    public function test_normalize_invoice_status(): void
    {
        $this->assertEquals(InvoiceStatus::UNBILLED, $this->transformer->normalizeInvoiceStatus('UNBILLED'));
        $this->assertEquals(InvoiceStatus::ISSUED, $this->transformer->normalizeInvoiceStatus('issued'));
        $this->assertEquals(InvoiceStatus::PAID, $this->transformer->normalizeInvoiceStatus('PAID'));
        $this->assertEquals(InvoiceStatus::UNBILLED, $this->transformer->normalizeInvoiceStatus(null));
    }

    public function test_normalize_billcomp_status(): void
    {
        $this->assertEquals(BillcompStatus::NOT_COMPLETE, $this->transformer->normalizeBillcompStatus('NOT_COMPLETE'));
        $this->assertEquals(BillcompStatus::NOT_COMPLETE, $this->transformer->normalizeBillcompStatus('Not Complete'));
        $this->assertEquals(BillcompStatus::PARTIAL, $this->transformer->normalizeBillcompStatus('PARTIAL'));
        $this->assertEquals(BillcompStatus::COMPLETED, $this->transformer->normalizeBillcompStatus('completed'));
        $this->assertEquals(BillcompStatus::NOT_COMPLETE, $this->transformer->normalizeBillcompStatus(null));
    }

    public function test_parse_percentage(): void
    {
        $this->assertEquals(75, $this->transformer->parsePercentage('75%'));
        $this->assertEquals(75, $this->transformer->parsePercentage('75'));
        $this->assertEquals(75, $this->transformer->parsePercentage(75));
        $this->assertEquals(75, $this->transformer->parsePercentage(0.75));
        $this->assertEquals(100, $this->transformer->parsePercentage('100%'));
        $this->assertEquals(0, $this->transformer->parsePercentage('0%'));
        $this->assertEquals(0, $this->transformer->parsePercentage(null));
    }

    public function test_normalize_sp_po(): void
    {
        $this->assertEquals('AVAILABLE', $this->transformer->normalizeSpPo('AVAILABLE'));
        $this->assertEquals('AVAILABLE', $this->transformer->normalizeSpPo('ADA'));
        $this->assertEquals('AVAILABLE', $this->transformer->normalizeSpPo('YES'));
        $this->assertEquals('MISSING', $this->transformer->normalizeSpPo('MISSING'));
        $this->assertEquals('MISSING', $this->transformer->normalizeSpPo(null));
    }

    // --- Row Transformation Tests ---

    public function test_transform_row_with_all_fields(): void
    {
        $headers = DataTransformer::STANDARD_HEADERS;
        $row = [
            'TENS-001',
            '2026',
            'LOP-2026-001',
            'TELKOM/B2B/2026/001',
            'PT Semen Indonesia Group',
            'Dinas Kominfo',
            'PT Maju Jaya',
            'Astinet 100 Mbps',
            'F3',
            'Rp 150.000.000',
            'Rp 120.000.000',
            '2026-01-01',
            '2026-12-31',
            '12',
            'Desember 2026',
            'Rp 10.000.000',
            'AVAILABLE',
            'ISSUED',
            'PARTIAL',
            '50%',
            '1DZzEKnxlwHFsd7_eC-_N4CtceGgCeJq5',
        ];

        $transformed = $this->transformer->transformRow($headers, $row, 2);

        $this->assertEquals(2, $transformed['_row_index']);
        $this->assertEquals('TENS-001', $transformed['id_mytens']);
        $this->assertEquals('2026', $transformed['tahun']);
        $this->assertEquals('LOP-2026-001', $transformed['lop']);
        $this->assertEquals('TELKOM/B2B/2026/001', $transformed['contract_number']);
        $this->assertEquals('PT Semen Indonesia Group', $transformed['nama_gc']);
        $this->assertEquals('PT Maju Jaya', $transformed['customer']);
        $this->assertEquals('Dinas Kominfo', $transformed['satker']);
        $this->assertEquals('Astinet 100 Mbps', $transformed['service']);
        $this->assertEquals('F3', $transformed['stage']);
        $this->assertEquals('Bidding', $transformed['stage_label']);
        $this->assertEquals(120000000, $transformed['revenue']);
        $this->assertEquals('Rp 120.000.000', $transformed['revenue_formatted']);
        $this->assertEquals(10000000, $transformed['nilai_bc']);
        $this->assertEquals('2026-01-01', $transformed['start_date']);
        $this->assertEquals('2026-12-31', $transformed['end_date']);
        $this->assertEquals(12, $transformed['durasi_bulan']);
        $this->assertEquals('AVAILABLE', $transformed['sp_po']);
        $this->assertEquals('ISSUED', $transformed['invoice_status']);
        $this->assertEquals('PARTIAL', $transformed['billcomp_status']);
        $this->assertEquals(50, $transformed['billcomp_percentage']);
        $this->assertEquals(60000000, $transformed['realized_revenue']);
        $this->assertEquals('Rp 60.000.000', $transformed['realized_revenue_formatted']);
        $this->assertEquals('1DZzEKnxlwHFsd7_eC-_N4CtceGgCeJq5', $transformed['document_reference']);
    }

    public function test_transform_all_skips_empty_rows(): void
    {
        $headers = DataTransformer::STANDARD_HEADERS;
        $row1 = array_fill(0, 21, '');
        $row1[0] = 'TENS-1';
        $row1[1] = '2026';
        $row1[2] = 'LOP-001';
        $row1[6] = 'Cust 1';

        $emptyRow = array_fill(0, 21, '');

        $row2 = array_fill(0, 21, '');
        $row2[0] = 'TENS-2';
        $row2[1] = '2026';
        $row2[2] = 'LOP-002';
        $row2[6] = 'Cust 2';

        $result = $this->transformer->transformAll($headers, [$row1, $emptyRow, $row2], 2);

        $this->assertCount(2, $result);
        $this->assertEquals('LOP-001', $result[0]['lop']);
        $this->assertEquals(2, $result[0]['_row_index']);
        $this->assertEquals('LOP-002', $result[1]['lop']);
        $this->assertEquals(4, $result[1]['_row_index']); // row index skipped the empty row
    }

    public function test_to_spreadsheet_row(): void
    {
        $contract = [
            'id_mytens' => 'TENS-999',
            'tahun' => '2026',
            'lop' => 'LOP-999',
            'contract_number' => 'CTR/999',
            'nama_gc' => 'GC Test',
            'satker' => 'Satker Y',
            'judul_proyek' => 'Client X',
            'customer' => 'Client X',
            'service' => 'Indibiz',
            'stage' => Stage::F4,
            'revenue' => 50000000,
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
            'sp_po' => 'AVAILABLE',
            'invoice_status' => InvoiceStatus::PAID,
            'billcomp_status' => BillcompStatus::COMPLETED,
            'billcomp_percentage' => 100,
            'document_reference' => 'doc_123',
        ];

        $row = $this->transformer->toSpreadsheetRow($contract);

        $this->assertCount(22, $row);
        $this->assertEquals('TENS-999', $row[0]);
        $this->assertEquals('2026', $row[1]);
        $this->assertEquals('LOP-999', $row[2]);
        $this->assertEquals('CTR/999', $row[3]);
        $this->assertEquals('GC Test', $row[4]);
        $this->assertEquals('Client X', $row[6]);
        $this->assertEquals('F4', $row[8]);
        $this->assertEquals(50000000, $row[10]);
        $this->assertEquals('PAID', $row[17]);
        $this->assertEquals('COMPLETED', $row[18]);
        $this->assertEquals('Kontrak Berjalan', $row[21]);
    }

    public function test_normalize_contract_status(): void
    {
        $this->assertEquals(\App\Enums\ContractStatus::BERJALAN, $this->transformer->normalizeContractStatus(null));
        $this->assertEquals(\App\Enums\ContractStatus::BERJALAN, $this->transformer->normalizeContractStatus(''));
        $this->assertEquals(\App\Enums\ContractStatus::BERJALAN, $this->transformer->normalizeContractStatus('BERJALAN'));
        $this->assertEquals(\App\Enums\ContractStatus::BERJALAN, $this->transformer->normalizeContractStatus('Kontrak Berjalan'));

        $this->assertEquals(\App\Enums\ContractStatus::SELESAI, $this->transformer->normalizeContractStatus('SELESAI'));
        $this->assertEquals(\App\Enums\ContractStatus::SELESAI, $this->transformer->normalizeContractStatus('Kontrak Selesai'));
        $this->assertEquals(\App\Enums\ContractStatus::SELESAI, $this->transformer->normalizeContractStatus('COMPLETED'));
    }

    public function test_backward_compatibility_with_legacy_headers(): void
    {
        $legacyHeaders = [
            'LOP', 'Contract Number', 'Customer', 'Satker', 'Service', 'Stage',
            'Revenue', 'Start Date', 'End Date', 'SP/PO', 'Invoice Status',
            'Billcomp Status', 'Billcomp Percentage', 'Document Reference',
        ];

        $row = [
            'LOP-OLD-1', 'CTR-OLD', 'Legacy Corp', 'Satker A', 'Internet', 'F2',
            '10000000', '2026-01-01', '2026-12-31', 'AVAILABLE', 'PAID',
            'COMPLETED', '100%', 'doc1',
        ];

        $res = $this->transformer->transformRow($legacyHeaders, $row, 2);

        $this->assertEquals('LOP-OLD-1', $res['lop']);
        $this->assertEquals('Legacy Corp', $res['customer']);
        $this->assertEquals(10000000, $res['revenue']);
        $this->assertEquals('2026', $res['tahun']); // auto-derived from start_date
    }
}

<?php

namespace App\Console\Commands;

use App\Models\Notification;
use App\Services\DataTransformer;
use App\Services\GoogleSheetsService;
use Google\Service\Sheets\ClearValuesRequest;
use Google\Service\Sheets\ValueRange;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Throwable;

class SeedDummyContractsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'contracts:seed-dummy {--reset : Clear all existing data rows before seeding}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clear existing sheet data and seed 10 realistic West Sumatra Pemda B2B dummy contracts';

    /**
     * Execute the console command.
     */
    public function handle(GoogleSheetsService $sheetsService): int
    {
        $this->info('=== Seeding 10 West Sumatra Regional Gov (Pemda) Contracts to Google Sheets ===');

        $spreadsheetId = $sheetsService->getSpreadsheetId();
        $sheetName = $sheetsService->getSheetName();
        $dummyContracts = $this->getDummyContractsData();

        try {
            $headers = $sheetsService->getHeaders();
            $lastCol = $this->getColumnLetter(count($headers) ?: 21);

            // 1. Clear all existing data rows (from row 2 onwards)
            $this->warn("Clearing existing data rows in {$sheetName}!A2:{$lastCol}100...");
            $clearRequest = new ClearValuesRequest();
            $sheetsService->getSheetsService()->spreadsheets_values->clear(
                $spreadsheetId,
                "{$sheetName}!A2:{$lastCol}100",
                $clearRequest
            );
            $this->info("Previous data rows cleared successfully.");

            // 2. Prepare new 10 rows
            $toInsert = [];
            $transformer = new DataTransformer();

            foreach ($dummyContracts as $contract) {
                $rowValues = $transformer->toSpreadsheetRow($contract, $headers);
                $toInsert[] = $rowValues;
                $this->line("Prepared: <info>{$contract['lop']}</info> | Satker: {$contract['satker']} | GC: {$contract['nama_gc']} | Stage: {$contract['stage']}");
            }

            // 3. Write 10 rows into A2:U11
            $endRow = 1 + count($toInsert);
            $targetRange = "{$sheetName}!A2:{$lastCol}{$endRow}";
            $this->info("Writing " . count($toInsert) . " contracts to {$targetRange}...");

            $valueRange = new ValueRange();
            $valueRange->setValues($toInsert);

            $sheetsService->getSheetsService()->spreadsheets_values->update(
                $spreadsheetId,
                $targetRange,
                $valueRange,
                ['valueInputOption' => 'USER_ENTERED']
            );

            // 4. Invalidate cache
            $sheetsService->clearCache();
            $this->info('Google Sheets cache invalidated.');

            // 5. Clean up old notifications and regenerate for new contracts
            $this->info('Refreshing notification alerts...');
            Notification::query()->delete();
            Artisan::call('contracts:check-expiration');
            $this->line("Contract expiration checks processed successfully.");

            $this->info("Successfully populated 10 fresh dummy contracts for {$sheetName}!");
            return Command::SUCCESS;
        } catch (Throwable $e) {
            $this->error('Failed to seed contracts: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }

    /**
     * Get the 10 realistic Pemda Sumatra Barat dummy contracts.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getDummyContractsData(): array
    {
        return [
            // 1. F4 Negotiation - Pemprov Sumatra Barat (Active)
            [
                'id_mytens' => 'MYT-2026-001',
                'tahun' => '2026',
                'lop' => 'LOP-SUMBAR-2026-001',
                'contract_number' => 'K.TEL.01/HK.810/SUMBAR/2026',
                'nama_gc' => 'Pemprov Sumatra Barat',
                'satker' => 'Dinas Komunikasi, Informatika dan Statistik Pemprov Sumbar',
                'judul_proyek' => 'Pengadaan Layanan Astinet Dedicated 1 Gbps & Data Center SPBE',
                'customer' => 'Pemprov Sumatra Barat',
                'service' => 'Astinet Dedicated 1 Gbps & Cloud SPBE',
                'stage' => 'F4',
                'estimasi_nilai_proyek' => 720000000,
                'revenue' => 660000000,
                'start_date' => '2026-01-01',
                'end_date' => '2026-12-31',
                'durasi_bulan' => 12,
                'estimasi_bulan_bc' => 'Januari',
                'nilai_bc' => 55000000,
                'sp_po' => 'AVAILABLE',
                'invoice_status' => 'ISSUED',
                'billcomp_status' => 'PARTIAL',
                'billcomp_percentage' => 65,
                'document_reference' => '1DZzEKnxlwHFsd7_eC-_N4CtceGgCeJq5',
            ],

            // 2. F3 Bidding - Pemkot Pariaman (Expiring Soon - 25 days)
            [
                'id_mytens' => 'MYT-2026-002',
                'tahun' => '2026',
                'lop' => 'LOP-PRM-2026-002',
                'contract_number' => 'K.TEL.02/HK.810/PRM/2026',
                'nama_gc' => 'Pemkot Pariaman',
                'satker' => 'Diskominfo Kota Pariaman',
                'judul_proyek' => 'Penyediaan Metro Ethernet Interkoneksi OPD Kota Pariaman',
                'customer' => 'Pemkot Pariaman',
                'service' => 'Metro Ethernet Interkoneksi 300 Mbps',
                'stage' => 'F3',
                'estimasi_nilai_proyek' => 420000000,
                'revenue' => 384000000,
                'start_date' => '2026-02-01',
                'end_date' => '2026-09-28',
                'durasi_bulan' => 12,
                'estimasi_bulan_bc' => 'Februari',
                'nilai_bc' => 32000000,
                'sp_po' => 'AVAILABLE',
                'invoice_status' => 'ISSUED',
                'billcomp_status' => 'PARTIAL',
                'billcomp_percentage' => 80,
                'document_reference' => '1DZzEKnxlwHFsd7_eC-_N4CtceGgCeJq5',
            ],

            // 3. F2 Quote - Pemkab Pasaman (Active)
            [
                'id_mytens' => 'MYT-2026-003',
                'tahun' => '2026',
                'lop' => 'LOP-PAS-2026-003',
                'contract_number' => 'K.TEL.03/HK.810/PAS/2026',
                'nama_gc' => 'Pemkab Pasaman',
                'satker' => 'Diskominfo Kabupaten Pasaman',
                'judul_proyek' => 'Layanan Astinet Dedicated Jaringan Intra Pemerintah Daerah',
                'customer' => 'Pemkab Pasaman',
                'service' => 'Astinet High Speed 200 Mbps',
                'stage' => 'F2',
                'estimasi_nilai_proyek' => 360000000,
                'revenue' => 320000000,
                'start_date' => '2026-03-01',
                'end_date' => '2027-02-28',
                'durasi_bulan' => 12,
                'estimasi_bulan_bc' => 'Maret',
                'nilai_bc' => 26666667,
                'sp_po' => 'AVAILABLE',
                'invoice_status' => 'UNBILLED',
                'billcomp_status' => 'NOT_COMPLETE',
                'billcomp_percentage' => 25,
                'document_reference' => '1DZzEKnxlwHFsd7_eC-_N4CtceGgCeJq5',
            ],

            // 4. F1 Opportunity - Pemprov Sumatra Barat (Active)
            [
                'id_mytens' => 'MYT-2026-004',
                'tahun' => '2026',
                'lop' => 'LOP-SUMBAR-2026-004',
                'contract_number' => 'K.TEL.04/HK.810/SUMBAR/2026',
                'nama_gc' => 'Pemprov Sumatra Barat',
                'satker' => 'Badan Pendapatan Daerah (Bapenda) Pemprov Sumbar',
                'judul_proyek' => 'Jaringan Komunikasi Data Online Samsat & Pajak Daerah Sumbar',
                'customer' => 'Pemprov Sumatra Barat',
                'service' => 'VPN IP & Cloud Hosting Transaksi Pajak',
                'stage' => 'F1',
                'estimasi_nilai_proyek' => 540000000,
                'revenue' => 480000000,
                'start_date' => '2026-04-01',
                'end_date' => '2027-03-31',
                'durasi_bulan' => 12,
                'estimasi_bulan_bc' => 'April',
                'nilai_bc' => 40000000,
                'sp_po' => 'MISSING',
                'invoice_status' => 'UNBILLED',
                'billcomp_status' => 'NOT_COMPLETE',
                'billcomp_percentage' => 0,
                'document_reference' => '',
            ],

            // 5. F0 Lead - Pemkot Pariaman (Active)
            [
                'id_mytens' => 'MYT-2026-005',
                'tahun' => '2026',
                'lop' => 'LOP-PRM-2026-005',
                'contract_number' => 'K.TEL.05/HK.810/PRM/2026',
                'nama_gc' => 'Pemkot Pariaman',
                'satker' => 'Dinas Pariwisata dan Kebudayaan Kota Pariaman',
                'judul_proyek' => 'Penyediaan WiFi Publik Kawasan Wisata Pantai Gandoriah',
                'customer' => 'Pemkot Pariaman',
                'service' => 'Managed Service WiFi High-Density & Portal Wisata',
                'stage' => 'F0',
                'estimasi_nilai_proyek' => 180000000,
                'revenue' => 150000000,
                'start_date' => '2026-05-01',
                'end_date' => '2027-04-30',
                'durasi_bulan' => 12,
                'estimasi_bulan_bc' => 'Mei',
                'nilai_bc' => 12500000,
                'sp_po' => 'MISSING',
                'invoice_status' => 'UNBILLED',
                'billcomp_status' => 'NOT_COMPLETE',
                'billcomp_percentage' => 0,
                'document_reference' => '',
            ],

            // 6. F4 Negotiation - Pemkab Pasaman (Tahun 2025 - OVERDUE)
            [
                'id_mytens' => 'MYT-2025-006',
                'tahun' => '2025',
                'lop' => 'LOP-PAS-2025-006',
                'contract_number' => 'K.TEL.06/HK.810/PAS/2025',
                'nama_gc' => 'Pemkab Pasaman',
                'satker' => 'RSUD Lubuk Sikaping Kabupaten Pasaman',
                'judul_proyek' => 'Integrasi Jaringan SIMRS & Telemedicine RSUD Lubuk Sikaping',
                'customer' => 'Pemkab Pasaman',
                'service' => 'Astinet Dedicated 100 Mbps & Backup Radio Link',
                'stage' => 'F4',
                'estimasi_nilai_proyek' => 260000000,
                'revenue' => 240000000,
                'start_date' => '2025-08-01',
                'end_date' => '2026-08-15',
                'durasi_bulan' => 12,
                'estimasi_bulan_bc' => 'Agustus',
                'nilai_bc' => 20000000,
                'sp_po' => 'AVAILABLE',
                'invoice_status' => 'PAID',
                'billcomp_status' => 'COMPLETED',
                'billcomp_percentage' => 100,
                'document_reference' => '1DZzEKnxlwHFsd7_eC-_N4CtceGgCeJq5',
            ],

            // 7. F3 Bidding - Pemprov Sumatra Barat (Tahun 2025 - Expiring Soon - 27 days)
            [
                'id_mytens' => 'MYT-2025-007',
                'tahun' => '2025',
                'lop' => 'LOP-SUMBAR-2025-007',
                'contract_number' => 'K.TEL.07/HK.810/SUMBAR/2025',
                'nama_gc' => 'Pemprov Sumatra Barat',
                'satker' => 'RSUD Dr. Achmad Mochtar Bukittinggi (Pemprov Sumbar)',
                'judul_proyek' => 'Layanan Konektivitas Astinet Dedicated Rumah Sakit Rujukan',
                'customer' => 'Pemprov Sumatra Barat',
                'service' => 'Astinet Dedicated 500 Mbps Redundant FO',
                'stage' => 'F3',
                'estimasi_nilai_proyek' => 500000000,
                'revenue' => 450000000,
                'start_date' => '2025-10-01',
                'end_date' => '2026-09-30',
                'durasi_bulan' => 12,
                'estimasi_bulan_bc' => 'Oktober',
                'nilai_bc' => 37500000,
                'sp_po' => 'AVAILABLE',
                'invoice_status' => 'ISSUED',
                'billcomp_status' => 'PARTIAL',
                'billcomp_percentage' => 90,
                'document_reference' => '1DZzEKnxlwHFsd7_eC-_N4CtceGgCeJq5',
            ],

            // 8. F2 Quote - Pemkot Pariaman (Active)
            [
                'id_mytens' => 'MYT-2026-008',
                'tahun' => '2026',
                'lop' => 'LOP-PRM-2026-008',
                'contract_number' => 'K.TEL.08/HK.810/PRM/2026',
                'nama_gc' => 'Pemkot Pariaman',
                'satker' => 'BPKPD (Badan Keuangan & Pendapatan) Kota Pariaman',
                'judul_proyek' => 'Penyediaan Cloud Server & Integrasi SIPD Keuangan Pariaman',
                'customer' => 'Pemkot Pariaman',
                'service' => 'Telkom Cloud IaaS & Security Monitoring',
                'stage' => 'F2',
                'estimasi_nilai_proyek' => 310000000,
                'revenue' => 280000000,
                'start_date' => '2026-06-01',
                'end_date' => '2027-05-31',
                'durasi_bulan' => 12,
                'estimasi_bulan_bc' => 'Juni',
                'nilai_bc' => 23333333,
                'sp_po' => 'AVAILABLE',
                'invoice_status' => 'UNBILLED',
                'billcomp_status' => 'PARTIAL',
                'billcomp_percentage' => 40,
                'document_reference' => '1DZzEKnxlwHFsd7_eC-_N4CtceGgCeJq5',
            ],

            // 9. F1 Opportunity - Pemkab Pasaman (Active)
            [
                'id_mytens' => 'MYT-2026-009',
                'tahun' => '2026',
                'lop' => 'LOP-PAS-2026-009',
                'contract_number' => 'K.TEL.09/HK.810/PAS/2026',
                'nama_gc' => 'Pemkab Pasaman',
                'satker' => 'Dinas Kependudukan dan Catatan Sipil Pemkab Pasaman',
                'judul_proyek' => 'Jaringan Khusus Pelayanan Administrasi Kependudukan SIAK',
                'customer' => 'Pemkab Pasaman',
                'service' => 'VPN IP Dedicated & Secure Access SIAK',
                'stage' => 'F1',
                'estimasi_nilai_proyek' => 220000000,
                'revenue' => 195000000,
                'start_date' => '2026-07-01',
                'end_date' => '2027-06-30',
                'durasi_bulan' => 12,
                'estimasi_bulan_bc' => 'Juli',
                'nilai_bc' => 16250000,
                'sp_po' => 'MISSING',
                'invoice_status' => 'UNBILLED',
                'billcomp_status' => 'NOT_COMPLETE',
                'billcomp_percentage' => 0,
                'document_reference' => '',
            ],

            // 10. F0 Lead - Pemprov Sumatra Barat (Active)
            [
                'id_mytens' => 'MYT-2026-010',
                'tahun' => '2026',
                'lop' => 'LOP-SUMBAR-2026-010',
                'contract_number' => 'K.TEL.10/HK.810/SUMBAR/2026',
                'nama_gc' => 'Pemprov Sumatra Barat',
                'satker' => 'Bappeda Provinsi Sumatera Barat',
                'judul_proyek' => 'Infrastruktur Satu Data Pembangunan Sumbar Madani',
                'customer' => 'Pemprov Sumatra Barat',
                'service' => 'Big Data Analytics Platform & Cloud Hosting',
                'stage' => 'F0',
                'estimasi_nilai_proyek' => 400000000,
                'revenue' => 350000000,
                'start_date' => '2026-08-01',
                'end_date' => '2027-07-31',
                'durasi_bulan' => 12,
                'estimasi_bulan_bc' => 'Agustus',
                'nilai_bc' => 29166667,
                'sp_po' => 'MISSING',
                'invoice_status' => 'UNBILLED',
                'billcomp_status' => 'NOT_COMPLETE',
                'billcomp_percentage' => 0,
                'document_reference' => '',
            ],
        ];
    }

    /**
     * Convert 1-based column number into Excel column letter.
     */
    private function getColumnLetter(int $columnNumber): string
    {
        $letter = '';
        while ($columnNumber > 0) {
            $remainder = ($columnNumber - 1) % 26;
            $letter = chr(65 + $remainder) . $letter;
            $columnNumber = (int) (($columnNumber - $remainder) / 26);
        }
        return $letter ?: 'U';
    }
}

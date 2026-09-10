<?php

namespace App\Services;

use App\Enums\BillcompStatus;
use App\Enums\ContractStatus;
use App\Enums\InvoiceStatus;
use App\Enums\Stage;
use Carbon\Carbon;

/**
 * DataTransformer — PRD FR-09
 *
 * Normalizes and formats data between raw Google Sheets rows and internal application structures.
 *
 * Column mapping (PRD Section 11):
 * A: LOP
 * B: Contract Number
 * C: Customer
 * D: Satker
 * E: Service
 * F: Stage (F0–F4)
 * G: Revenue (Currency/Number)
 * H: Start Date (Date)
 * I: End Date (Date)
 * J: SP/PO (AVAILABLE/MISSING)
 * K: Invoice Status (UNBILLED/ISSUED/PAID)
 * L: Billcomp Status (NOT_COMPLETE/PARTIAL/COMPLETED)
 * M: Billcomp Percentage (0–100%)
 * N: Document Reference (Drive File ID/URL)
 */
class DataTransformer
{
    /**
     * Standard headers in order (21 columns matching Google Sheets).
     */
    public const STANDARD_HEADERS = [
        'ID MyTens',
        'Tahun',
        'LOP',
        'Contract Number',
        'Nama GC',
        'Satker',
        'Judul Proyek',
        'Deskripsi Layanan',
        'Stage',
        'Estimasi Nilai Proyek',
        'Nilai Realisasi win',
        'Start Date',
        'End Date',
        'Estimasi Durasi Pemakaian (Bulan)',
        'Estimasi Bulan BC',
        'Nilai BC',
        'SP/PO',
        'Invoice Status',
        'Billcomp Status',
        'Billcomp Percentage',
        'Document Reference',
        'Status Kontrak',
    ];

    /**
     * Header name to internal key mapping.
     */
    protected const HEADER_MAP = [
        // ID MyTens
        'id mytens' => 'id_mytens',
        'id_mytens' => 'id_mytens',
        'mytens' => 'id_mytens',

        // Tahun
        'tahun' => 'tahun',
        'year' => 'tahun',

        // LOP
        'lop' => 'lop',

        // Contract Number
        'contract number' => 'contract_number',
        'contract_number' => 'contract_number',
        'nomor kontrak' => 'contract_number',

        // Nama GC
        'nama gc' => 'nama_gc',
        'nama_gc' => 'nama_gc',
        'gc' => 'nama_gc',

        // Satker
        'satker' => 'satker',

        // Judul Proyek & Customer
        'judul proyek' => 'judul_proyek',
        'judul_proyek' => 'judul_proyek',
        'customer' => 'customer',
        'pelanggan' => 'customer',

        // Deskripsi Layanan & Service
        'deskripsi layanan' => 'service',
        'deskripsi_layanan' => 'service',
        'service' => 'service',
        'layanan' => 'service',

        // Stage
        'stage' => 'stage',
        'tahapan' => 'stage',

        // Estimasi Nilai Proyek
        'estimasi nilai proyek' => 'estimasi_nilai_proyek',
        'estimasi_nilai_proyek' => 'estimasi_nilai_proyek',

        // Nilai Realisasi win (Revenue)
        'nilai realisasi win' => 'revenue',
        'nilai_realisasi_win' => 'revenue',
        'realisasi win' => 'revenue',
        'revenue' => 'revenue',
        'nilai kontrak' => 'revenue',

        // Dates
        'start date' => 'start_date',
        'start_date' => 'start_date',
        'tanggal mulai' => 'start_date',
        'end date' => 'end_date',
        'end_date' => 'end_date',
        'tanggal selesai' => 'end_date',

        // Estimasi Durasi Pemakaian (Bulan)
        'estimasi durasi pemakaian (bulan)' => 'durasi_bulan',
        'estimasi durasi pemakaian' => 'durasi_bulan',
        'durasi pemakaian (bulan)' => 'durasi_bulan',
        'durasi pemakaian' => 'durasi_bulan',
        'durasi_bulan' => 'durasi_bulan',

        // Estimasi Bulan BC
        'estimasi bulan bc' => 'estimasi_bulan_bc',
        'estimasi_bulan_bc' => 'estimasi_bulan_bc',
        'bulan bc' => 'estimasi_bulan_bc',

        // Nilai BC
        'nilai bc' => 'nilai_bc',
        'nilai_bc' => 'nilai_bc',

        // SP/PO
        'sp/po' => 'sp_po',
        'sp_po' => 'sp_po',
        'sp po' => 'sp_po',

        // Invoice Status
        'invoice status' => 'invoice_status',
        'invoice_status' => 'invoice_status',
        'status invoice' => 'invoice_status',

        // Billcomp Status
        'billcomp status' => 'billcomp_status',
        'billcomp_status' => 'billcomp_status',
        'status billcomp' => 'billcomp_status',

        // Billcomp Percentage
        'billcomp percentage' => 'billcomp_percentage',
        'billcomp_percentage' => 'billcomp_percentage',
        'persentase billcomp' => 'billcomp_percentage',

        // Document Reference
        'document reference' => 'document_reference',
        'document_reference' => 'document_reference',
        'dokumen' => 'document_reference',

        // Status Kontrak
        'status kontrak' => 'status_kontrak',
        'status_kontrak' => 'status_kontrak',
        'status' => 'status_kontrak',
    ];

    /**
     * Parse raw currency string into integer.
     * Examples:
     * - "Rp 150.000.000" -> 150000000
     * - "Rp. 150.000.000,00" -> 150000000
     * - "150,000,000" -> 150000000
     * - "150000000" -> 150000000
     * - null / empty -> 0
     */
    public function parseCurrency(mixed $value): int
    {
        if ($value === null || $value === '') {
            return 0;
        }

        if (is_numeric($value)) {
            return (int) round((float) $value);
        }

        $str = (string) $value;

        // Check if Indonesian format with decimals (e.g., "150.000.000,00")
        if (preg_match('/,(\d{2})$/', $str)) {
            $str = preg_replace('/,(\d{2})$/', '', $str);
        }

        // Remove non-digit characters except minus sign
        $clean = preg_replace('/[^\d\-]/', '', $str);

        return (int) ($clean === '' ? 0 : $clean);
    }

    /**
     * Format integer into Indonesian Rupiah representation.
     * Example: 150000000 -> "Rp 150.000.000"
     */
    public function formatCurrency(int|float|null $value): string
    {
        $amount = (int) ($value ?? 0);
        return 'Rp ' . number_format($amount, 0, ',', '.');
    }

    /**
     * Parse and normalize date into Y-m-d format.
     * Supports formats: Y-m-d, d/m/Y, d-m-Y, Y/m/d, d M Y, etc.
     */
    public function parseDate(mixed $value): ?string
    {
        if ($value === null || trim((string) $value) === '') {
            return null;
        }

        $str = trim((string) $value);

        // Handle Excel numeric serial dates (e.g. 45200)
        if (is_numeric($str) && (int) $str > 30000 && (int) $str < 60000) {
            $unixTime = ((int) $str - 25569) * 86400;
            return gmdate('Y-m-d', $unixTime);
        }

        $formats = [
            'Y-m-d',
            'd/m/Y',
            'd-m-Y',
            'Y/m/d',
            'd.m.Y',
            'j/n/Y',
            'j-n-Y',
            'd M Y',
            'd F Y',
            'Y-m-d H:i:s',
        ];

        foreach ($formats as $format) {
            try {
                $date = Carbon::createFromFormat($format, $str);
                if ($date !== false) {
                    return $date->format('Y-m-d');
                }
            } catch (\Exception) {
                continue;
            }
        }

        $timestamp = @strtotime($str);
        if ($timestamp !== false) {
            return date('Y-m-d', $timestamp);
        }

        return null;
    }

    /**
     * Normalize stage to F0–F4.
     */
    public function normalizeStage(?string $value): Stage
    {
        if (!$value) {
            return Stage::F0;
        }

        $clean = strtoupper(trim($value));

        // Match exact enum values
        foreach (Stage::cases() as $case) {
            if ($clean === $case->value || str_starts_with($clean, $case->value)) {
                return $case;
            }
            if (str_contains($clean, strtoupper($case->label()))) {
                return $case;
            }
        }

        return Stage::F0;
    }

    /**
     * Normalize invoice status to UNBILLED/ISSUED/PAID.
     */
    public function normalizeInvoiceStatus(?string $value): InvoiceStatus
    {
        if (!$value) {
            return InvoiceStatus::UNBILLED;
        }

        $clean = strtoupper(trim($value));

        foreach (InvoiceStatus::cases() as $case) {
            if ($clean === $case->value || $clean === strtoupper($case->label())) {
                return $case;
            }
        }

        return InvoiceStatus::UNBILLED;
    }

    /**
     * Normalize billcomp status to NOT_COMPLETE/PARTIAL/COMPLETED.
     */
    public function normalizeBillcompStatus(?string $value): BillcompStatus
    {
        if (!$value) {
            return BillcompStatus::NOT_COMPLETE;
        }

        $clean = strtoupper(trim(str_replace(' ', '_', $value)));

        foreach (BillcompStatus::cases() as $case) {
            if ($clean === $case->value || $clean === strtoupper(str_replace(' ', '_', $case->label()))) {
                return $case;
            }
        }

        return BillcompStatus::NOT_COMPLETE;
    }

    /**
     * Parse percentage to integer 0–100.
     * Examples: "75%", "75", 0.75 -> 75
     */
    public function parsePercentage(mixed $value): int
    {
        if ($value === null || $value === '') {
            return 0;
        }

        if (is_numeric($value)) {
            $num = (float) $value;
            // If decimal <= 1 and > 0, assume fraction (e.g., 0.75 -> 75)
            if ($num > 0 && $num <= 1) {
                $num = $num * 100;
            }
            return (int) max(0, min(100, round($num)));
        }

        $clean = preg_replace('/[^\d.]/', '', (string) $value);
        if ($clean === '') {
            return 0;
        }

        $num = (float) $clean;
        if ($num > 0 && $num <= 1) {
            $num = $num * 100;
        }

        return (int) max(0, min(100, round($num)));
    }

    /**
     * Normalize SP/PO status: AVAILABLE / MISSING.
     */
    public function normalizeSpPo(?string $value): string
    {
        if (!$value) {
            return 'MISSING';
        }

        $clean = strtoupper(trim($value));

        if (in_array($clean, ['AVAILABLE', 'ADA', 'YES', 'TRUE', '1'], true)) {
            return 'AVAILABLE';
        }

        return 'MISSING';
    }

    /**
     * Normalize Contract Status: BERJALAN / SELESAI.
     */
    public function normalizeContractStatus(?string $value): ContractStatus
    {
        if (!$value) {
            return ContractStatus::BERJALAN;
        }

        $clean = strtoupper(trim($value));

        if (in_array($clean, ['SELESAI', 'KONTRAK SELESAI', 'COMPLETED', 'FINISHED', 'DONE', 'CLOSED'], true)) {
            return ContractStatus::SELESAI;
        }

        return ContractStatus::BERJALAN;
    }

    /**
     * Transform a single raw spreadsheet row into a standardized array.
     *
     * @param array<int, string> $headers Header row from spreadsheet
     * @param array<int, mixed> $row Data row from spreadsheet
     * @param int|null $rowIndex 1-based row index in spreadsheet
     * @return array<string, mixed>
     */
    public function transformRow(array $headers, array $row, ?int $rowIndex = null): array
    {
        $raw = [];

        foreach ($headers as $index => $header) {
            $headerKey = strtolower(trim((string) $header));
            $internalKey = self::HEADER_MAP[$headerKey] ?? 'col_' . $index;
            $raw[$internalKey] = $row[$index] ?? null;
        }

        $idMyTens = trim((string) ($raw['id_mytens'] ?? ''));
        $lop = trim((string) ($raw['lop'] ?? ''));
        $contractNumber = trim((string) ($raw['contract_number'] ?? ''));
        $namaGc = trim((string) ($raw['nama_gc'] ?? ''));
        $satker = trim((string) ($raw['satker'] ?? ''));
        $judulProyek = trim((string) ($raw['judul_proyek'] ?? ''));

        // Customer: use customer if exists, or fallback to judul_proyek / nama_gc
        $customer = trim((string) ($raw['customer'] ?? ''));
        if (empty($customer)) {
            $customer = !empty($judulProyek) ? $judulProyek : (!empty($namaGc) ? $namaGc : '');
        }

        $service = trim((string) ($raw['service'] ?? ''));
        $stage = $this->normalizeStage($raw['stage'] ?? null);

        // Estimasi Nilai Proyek
        $estimasiNilaiProyek = $this->parseCurrency($raw['estimasi_nilai_proyek'] ?? null);

        // Revenue = Nilai Realisasi win
        $revenue = $this->parseCurrency($raw['revenue'] ?? null);

        $startDate = $this->parseDate($raw['start_date'] ?? null);
        $endDate = $this->parseDate($raw['end_date'] ?? null);

        // Tahun: from column B, or intelligent fallback to year of start_date or end_date
        $tahunRaw = trim((string) ($raw['tahun'] ?? ''));
        $tahun = !empty($tahunRaw)
            ? $tahunRaw
            : ($startDate ? substr($startDate, 0, 4) : ($endDate ? substr($endDate, 0, 4) : ''));

        // Durasi pemakaian (bulan)
        $durasiBulanRaw = $raw['durasi_bulan'] ?? null;
        $durasiBulan = is_numeric($durasiBulanRaw)
            ? (float) $durasiBulanRaw
            : (is_string($durasiBulanRaw) && preg_match('/[\d\.]+/', $durasiBulanRaw, $m) ? (float) $m[0] : 0);

        // Estimasi Bulan BC
        $estimasiBulanBc = trim((string) ($raw['estimasi_bulan_bc'] ?? ''));

        // Nilai BC (Nilai Realisasi win / Estimasi Durasi Pemakaian)
        $nilaiBcRaw = $raw['nilai_bc'] ?? null;
        if ($nilaiBcRaw !== null && $nilaiBcRaw !== '') {
            $nilaiBc = $this->parseCurrency($nilaiBcRaw);
        } else {
            $nilaiBc = ($durasiBulan > 0 && $revenue > 0) ? (int) round($revenue / $durasiBulan) : 0;
        }

        $spPo = $this->normalizeSpPo($raw['sp_po'] ?? null);
        $invoiceStatus = $this->normalizeInvoiceStatus($raw['invoice_status'] ?? null);
        $billcompStatus = $this->normalizeBillcompStatus($raw['billcomp_status'] ?? null);
        $billcompPercentage = $this->parsePercentage($raw['billcomp_percentage'] ?? null);
        $statusKontrak = $this->normalizeContractStatus($raw['status_kontrak'] ?? null);

        // Calculate realized revenue based on billcomp percentage
        $realizedRevenue = (int) round(($revenue * $billcompPercentage) / 100);

        return [
            '_row_index' => $rowIndex,
            'id_mytens' => $idMyTens,
            'tahun' => $tahun,
            'lop' => $lop,
            'contract_number' => $contractNumber,
            'nama_gc' => $namaGc,
            'satker' => $satker,
            'judul_proyek' => $judulProyek,
            'customer' => $customer,
            'service' => $service,
            'deskripsi_layanan' => $service,
            'stage' => $stage->value,
            'stage_label' => $stage->label(),
            'estimasi_nilai_proyek' => $estimasiNilaiProyek,
            'estimasi_nilai_proyek_formatted' => $this->formatCurrency($estimasiNilaiProyek),
            'revenue' => $revenue,
            'revenue_formatted' => $this->formatCurrency($revenue),
            'nilai_realisasi_win' => $revenue,
            'nilai_realisasi_win_formatted' => $this->formatCurrency($revenue),
            'start_date' => $startDate,
            'end_date' => $endDate,
            'durasi_bulan' => $durasiBulan,
            'estimasi_durasi_bulan' => $durasiBulan,
            'estimasi_bulan_bc' => $estimasiBulanBc,
            'nilai_bc' => $nilaiBc,
            'nilai_bc_formatted' => $this->formatCurrency($nilaiBc),
            'sp_po' => $spPo,
            'invoice_status' => $invoiceStatus->value,
            'invoice_status_label' => $invoiceStatus->label(),
            'billcomp_status' => $billcompStatus->value,
            'billcomp_status_label' => $billcompStatus->label(),
            'billcomp_percentage' => $billcompPercentage,
            'realized_revenue' => $realizedRevenue,
            'realized_revenue_formatted' => $this->formatCurrency($realizedRevenue),
            'document_reference' => trim((string) ($raw['document_reference'] ?? '')),
            'status_kontrak' => $statusKontrak->value,
            'status_kontrak_label' => $statusKontrak->label(),
            'is_completed' => $statusKontrak === ContractStatus::SELESAI,
        ];
    }

    /**
     * Batch transform all rows.
     *
     * @param array<int, string> $headers
     * @param array<int, array<int, mixed>> $rows
     * @param int $startRowIndex 1-based index where data rows start (typically 2 if header is 1)
     * @return array<int, array<string, mixed>>
     */
    public function transformAll(array $headers, array $rows, int $startRowIndex = 2): array
    {
        $transformed = [];

        foreach ($rows as $offset => $row) {
            // Skip completely empty rows
            if (empty(array_filter($row, fn ($val) => $val !== null && trim((string) $val) !== ''))) {
                continue;
            }

            $rowIndex = $startRowIndex + $offset;
            $transformedRow = $this->transformRow($headers, $row, $rowIndex);

            // Only include rows with a valid LOP
            if (!empty($transformedRow['lop'])) {
                $transformed[] = $transformedRow;
            }
        }

        return $transformed;
    }

    /**
     * Convert a structured contract array into a flat spreadsheet row array.
     *
     * @param array<string, mixed> $contract
     * @param array<int, string>|null $headers
     * @return array<int, mixed>
     */
    public function toSpreadsheetRow(array $contract, ?array $headers = null): array
    {
        $headers = $headers ?: self::STANDARD_HEADERS;
        $row = [];

        foreach ($headers as $header) {
            $headerKey = strtolower(trim($header));
            $key = self::HEADER_MAP[$headerKey] ?? null;

            $val = match ($key) {
                'id_mytens' => $contract['id_mytens'] ?? '',
                'tahun' => $contract['tahun'] ?? '',
                'lop' => $contract['lop'] ?? '',
                'contract_number' => $contract['contract_number'] ?? '',
                'nama_gc' => $contract['nama_gc'] ?? '',
                'satker' => $contract['satker'] ?? '',
                'judul_proyek' => $contract['judul_proyek'] ?? ($contract['customer'] ?? ''),
                'customer' => $contract['customer'] ?? ($contract['judul_proyek'] ?? ''),
                'service' => $contract['service'] ?? ($contract['deskripsi_layanan'] ?? ''),
                'stage' => ($contract['stage'] ?? null) instanceof Stage ? $contract['stage']->value : ($contract['stage'] ?? 'F0'),
                'estimasi_nilai_proyek' => (int) ($contract['estimasi_nilai_proyek'] ?? 0),
                'revenue' => (int) ($contract['revenue'] ?? $contract['nilai_realisasi_win'] ?? 0),
                'start_date' => $contract['start_date'] ?? '',
                'end_date' => $contract['end_date'] ?? '',
                'durasi_bulan' => $contract['durasi_bulan'] ?? $contract['estimasi_durasi_bulan'] ?? '',
                'estimasi_bulan_bc' => $contract['estimasi_bulan_bc'] ?? '',
                'nilai_bc' => (int) ($contract['nilai_bc'] ?? 0),
                'sp_po' => $contract['sp_po'] ?? 'MISSING',
                'invoice_status' => ($contract['invoice_status'] ?? null) instanceof InvoiceStatus ? $contract['invoice_status']->value : ($contract['invoice_status'] ?? 'UNBILLED'),
                'billcomp_status' => ($contract['billcomp_status'] ?? null) instanceof BillcompStatus ? $contract['billcomp_status']->value : ($contract['billcomp_status'] ?? 'NOT_COMPLETE'),
                'billcomp_percentage' => (string) ($contract['billcomp_percentage'] ?? '0%'),
                'document_reference' => $contract['document_reference'] ?? '',
                'status_kontrak' => ($contract['status_kontrak'] ?? null) instanceof ContractStatus
                    ? $contract['status_kontrak']->label()
                    : ((($contract['status_kontrak'] ?? '') === 'SELESAI' || ($contract['status_kontrak'] ?? '') === 'Kontrak Selesai') ? 'Kontrak Selesai' : 'Kontrak Berjalan'),
                default => '',
            };

            $row[] = $val;
        }

        return $row;
    }
}

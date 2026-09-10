<?php

namespace App\Services;

use App\Enums\Stage;
use App\Models\AuditLog;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use RuntimeException;

/**
 * ContractService — PRD FR-03, FR-04, FR-05, FR-06, FR-07
 *
 * Orchestrates GoogleSheetsService, DataTransformer, and ExpirationService.
 * Google Sheets remains the Single Source of Truth.
 */
class ContractService
{
    public function __construct(
        protected GoogleSheetsService $sheetsService,
        protected ExpirationService $expirationService,
        protected DataTransformer $transformer
    ) {}

    /**
     * Get all contracts, enriched with expiration calculations.
     *
     * @param bool $forceRefresh
     * @return array<int, array<string, mixed>>
     */
    public function getAllContracts(bool $forceRefresh = false): array
    {
        $rawContracts = $this->sheetsService->getAllContracts($forceRefresh);
        return $this->expirationService->enrichAll($rawContracts);
    }

    /**
     * Find a contract by LOP, enriched with expiration calculations.
     *
     * @param string $lop
     * @return array<string, mixed>|null
     */
    public function findByLop(string $lop): ?array
    {
        $contract = $this->sheetsService->findByLop($lop);

        if (!$contract) {
            return null;
        }

        return $this->expirationService->enrichContract($contract);
    }

    /**
     * Create a new contract in Google Sheets.
     *
     * @param array<string, mixed> $data
     * @param int|null $userId
     * @return array<string, mixed>
     */
    public function createContract(array $data, ?int $userId = null): array
    {
        $validated = $this->validateContractData($data, isUpdate: false);

        $created = $this->sheetsService->appendContract($validated, $userId);
        $enriched = $this->expirationService->enrichContract($created);

        // Audit Log
        AuditLog::record(
            action: 'contract_create',
            userId: $userId ?: auth()->id(),
            targetType: 'contract',
            targetReference: $enriched['lop'],
            metadata: [
                'customer' => $enriched['customer'],
                'revenue' => $enriched['revenue'],
                'stage' => $enriched['stage'],
            ]
        );

        return $enriched;
    }

    /**
     * Update an existing contract in Google Sheets by LOP.
     *
     * @param string $lop
     * @param array<string, mixed> $data
     * @param int|null $userId
     * @return array<string, mixed>
     */
    public function updateContract(string $lop, array $data, ?int $userId = null): array
    {
        $validated = $this->validateContractData($data, isUpdate: true);

        $existing = $this->findByLop($lop);

        if (!$existing) {
            throw new RuntimeException("Kontrak dengan LOP '{$lop}' tidak ditemukan.");
        }

        $updated = $this->sheetsService->updateByLop($lop, $validated, $userId);
        $enriched = $this->expirationService->enrichContract($updated);

        // Audit Log
        AuditLog::record(
            action: 'contract_update',
            userId: $userId ?: auth()->id(),
            targetType: 'contract',
            targetReference: $lop,
            metadata: [
                'old_stage' => $existing['stage'] ?? null,
                'new_stage' => $enriched['stage'] ?? null,
                'old_revenue' => $existing['revenue'] ?? null,
                'new_revenue' => $enriched['revenue'] ?? null,
                'old_status_kontrak' => $existing['status_kontrak'] ?? null,
                'new_status_kontrak' => $enriched['status_kontrak'] ?? null,
            ]
        );

        return $enriched;
    }

    /**
     * Mark an overdue contract as completed (Kontrak Selesai) manually by AM.
     *
     * @param string $lop
     * @param int|null $userId
     * @return array<string, mixed>
     */
    public function completeContract(string $lop, ?int $userId = null): array
    {
        $existing = $this->findByLop($lop);

        if (!$existing) {
            throw new RuntimeException("Kontrak dengan LOP '{$lop}' tidak ditemukan.");
        }

        $updated = $this->sheetsService->updateByLop($lop, [
            'status_kontrak' => 'Kontrak Selesai',
        ], $userId);

        $enriched = $this->expirationService->enrichContract($updated);

        // Audit Log
        AuditLog::record(
            action: 'contract_status_completed',
            userId: $userId ?: auth()->id(),
            targetType: 'contract',
            targetReference: $lop,
            metadata: [
                'customer' => $enriched['customer'] ?? null,
                'previous_status' => $existing['status_kontrak'] ?? 'BERJALAN',
                'new_status' => 'SELESAI',
                'is_overdue' => $existing['is_overdue'] ?? false,
                'days_remaining' => $existing['days_remaining'] ?? null,
            ]
        );

        return $enriched;
    }

    /**
     * Compute KPI metrics summary across all contracts.
     *
     * @param array<int, array<string, mixed>>|null $contracts
     * @return array<string, mixed>
     */
    public function getKpiSummary(?array $contracts = null): array
    {
        $contracts = $contracts !== null ? $contracts : $this->getAllContracts();

        $totalPipelineRevenue = 0;
        $totalRealizedRevenue = 0;
        $totalNilaiBc = 0;
        $totalLop = count($contracts);
        $activeCount = 0;
        $expiringSoonCount = 0;
        $overdueCount = 0;

        // Pipeline Stages breakdown (F0–F4)
        $stageSummary = [];
        foreach (Stage::cases() as $case) {
            $stageSummary[$case->value] = [
                'stage' => $case->value,
                'label' => $case->label(),
                'full_label' => $case->fullLabel(),
                'count' => 0,
                'total_revenue' => 0,
                'contracts' => [],
            ];
        }

        // Invoice & Billcomp counters
        $invoiceCounts = ['UNBILLED' => 0, 'ISSUED' => 0, 'PAID' => 0];
        $billcompCounts = ['NOT_COMPLETE' => 0, 'PARTIAL' => 0, 'COMPLETED' => 0];

        foreach ($contracts as $contract) {
            $revenue = (int) ($contract['revenue'] ?? 0);
            $realizedRevenue = (int) ($contract['realized_revenue'] ?? 0);
            $nilaiBc = (int) ($contract['nilai_bc'] ?? 0);
            $stage = $contract['stage'] ?? 'F0';
            $invoice = $contract['invoice_status'] ?? 'UNBILLED';
            $billcomp = $contract['billcomp_status'] ?? 'NOT_COMPLETE';

            $totalPipelineRevenue += $revenue;
            $totalRealizedRevenue += $realizedRevenue;
            $totalNilaiBc += $nilaiBc;

            if ($contract['is_active_contract'] ?? false) {
                $activeCount++;
            }
            if ($contract['is_expiring_soon'] ?? false) {
                $expiringSoonCount++;
            }
            if ($contract['is_overdue'] ?? false) {
                $overdueCount++;
            }

            if (isset($stageSummary[$stage])) {
                $stageSummary[$stage]['count']++;
                $stageSummary[$stage]['total_revenue'] += $revenue;
                $stageSummary[$stage]['contracts'][] = $contract;
            }

            if (isset($invoiceCounts[$invoice])) {
                $invoiceCounts[$invoice]++;
            }

            if (isset($billcompCounts[$billcomp])) {
                $billcompCounts[$billcomp]++;
            }
        }

        $realizedPercentage = $totalPipelineRevenue > 0
            ? round(($totalRealizedRevenue / $totalPipelineRevenue) * 100, 1)
            : 0;

        return [
            'total_pipeline_revenue' => $totalPipelineRevenue,
            'total_pipeline_revenue_formatted' => $this->transformer->formatCurrency($totalPipelineRevenue),
            'total_realized_revenue' => $totalRealizedRevenue,
            'total_realized_revenue_formatted' => $this->transformer->formatCurrency($totalRealizedRevenue),
            'total_nilai_bc' => $totalNilaiBc,
            'total_nilai_bc_formatted' => $this->transformer->formatCurrency($totalNilaiBc),
            'realized_percentage' => $realizedPercentage,
            'total_lop' => $totalLop,
            'active_contracts' => $activeCount,
            'expiring_soon_contracts' => $expiringSoonCount,
            'overdue_contracts' => $overdueCount,
            'stages' => $stageSummary,
            'invoice_counts' => $invoiceCounts,
            'billcomp_counts' => $billcompCounts,
        ];
    }

    /**
     * Search contracts across multiple fields (PRD FR-05).
     * Searchable fields: LOP, ID MyTens, Contract Number, Customer, Nama GC, Judul Proyek, Satker, Service, Tahun.
     *
     * @param string $query
     * @param array<int, array<string, mixed>>|null $contracts
     * @return array<int, array<string, mixed>>
     */
    public function searchContracts(?string $query = '', ?array $contracts = null): array
    {
        $contracts = $contracts !== null ? $contracts : $this->getAllContracts();
        $trimmedQuery = trim((string) $query);

        if ($trimmedQuery === '') {
            return $contracts;
        }

        $lowercaseQuery = mb_strtolower($trimmedQuery);

        return array_values(array_filter($contracts, function (array $contract) use ($lowercaseQuery) {
            $searchableFields = [
                $contract['lop'] ?? '',
                $contract['id_mytens'] ?? '',
                $contract['contract_number'] ?? '',
                $contract['customer'] ?? '',
                $contract['nama_gc'] ?? '',
                $contract['judul_proyek'] ?? '',
                $contract['satker'] ?? '',
                $contract['service'] ?? '',
                $contract['tahun'] ?? '',
            ];

            foreach ($searchableFields as $field) {
                if (str_contains(mb_strtolower((string) $field), $lowercaseQuery)) {
                    return true;
                }
            }

            return false;
        }));
    }

    /**
     * Filter contracts by specified criteria (PRD FR-05).
     * Supported filters: tahun, nama_gc, stage, satker, service, invoice_status, billcomp_status, expiration_status, sp_po, revenue_min, revenue_max.
     *
     * @param array<string, mixed> $filters
     * @param array<int, array<string, mixed>>|null $contracts
     * @return array<int, array<string, mixed>>
     */
    public function filterContracts(array $filters, ?array $contracts = null): array
    {
        $contracts = $contracts !== null ? $contracts : $this->getAllContracts();

        if (empty($filters)) {
            return $contracts;
        }

        return array_values(array_filter($contracts, function (array $contract) use ($filters) {
            // Filter by Tahun (Year: "all" or specific year e.g. "2026")
            if (!empty($filters['tahun']) && strtolower((string) $filters['tahun']) !== 'all') {
                $years = is_array($filters['tahun']) ? $filters['tahun'] : [$filters['tahun']];
                $years = array_map('strval', $years);
                $contractYear = (string) ($contract['tahun'] ?? '');
                if ($contractYear === '' && !empty($contract['start_date'])) {
                    $contractYear = substr((string) $contract['start_date'], 0, 4);
                }
                if (!in_array($contractYear, $years, true)) {
                    return false;
                }
            }

            // Filter by Nama GC ("all" or specific company name)
            if (!empty($filters['nama_gc']) && strtolower((string) $filters['nama_gc']) !== 'all') {
                $gcQuery = mb_strtolower(trim((string) $filters['nama_gc']));
                $contractGc = mb_strtolower(trim((string) ($contract['nama_gc'] ?? '')));
                $contractCustomer = mb_strtolower(trim((string) ($contract['customer'] ?? '')));
                if ($contractGc !== $gcQuery && !str_contains($contractGc, $gcQuery) && !str_contains($contractCustomer, $gcQuery)) {
                    return false;
                }
            }

            // Filter by Stage (string or array)
            if (!empty($filters['stage'])) {
                $stages = is_array($filters['stage']) ? $filters['stage'] : [$filters['stage']];
                $stages = array_map('strtoupper', array_map('trim', $stages));
                if (!in_array(strtoupper($contract['stage'] ?? ''), $stages, true)) {
                    return false;
                }
            }

            // Filter by Satker (partial match)
            if (!empty($filters['satker'])) {
                $satkerQuery = mb_strtolower(trim($filters['satker']));
                if (!str_contains(mb_strtolower((string) ($contract['satker'] ?? '')), $satkerQuery)) {
                    return false;
                }
            }

            // Filter by Service (partial match)
            if (!empty($filters['service'])) {
                $serviceQuery = mb_strtolower(trim($filters['service']));
                if (!str_contains(mb_strtolower((string) ($contract['service'] ?? '')), $serviceQuery)) {
                    return false;
                }
            }

            // Filter by Invoice Status (string or array)
            if (!empty($filters['invoice_status'])) {
                $statuses = is_array($filters['invoice_status']) ? $filters['invoice_status'] : [$filters['invoice_status']];
                $statuses = array_map('strtoupper', array_map('trim', $statuses));
                if (!in_array(strtoupper($contract['invoice_status'] ?? ''), $statuses, true)) {
                    return false;
                }
            }

            // Filter by Billcomp Status (string or array)
            if (!empty($filters['billcomp_status'])) {
                $statuses = is_array($filters['billcomp_status']) ? $filters['billcomp_status'] : [$filters['billcomp_status']];
                $statuses = array_map('strtoupper', array_map('trim', $statuses));
                if (!in_array(strtoupper($contract['billcomp_status'] ?? ''), $statuses, true)) {
                    return false;
                }
            }

            // Filter by Expiration Status (string or array: ACTIVE, EXPIRING_SOON, OVERDUE)
            if (!empty($filters['expiration_status'])) {
                $statuses = is_array($filters['expiration_status']) ? $filters['expiration_status'] : [$filters['expiration_status']];
                $statuses = array_map('strtoupper', array_map('trim', $statuses));
                if (!in_array(strtoupper($contract['expiration_status'] ?? ''), $statuses, true)) {
                    return false;
                }
            }

            // Filter by Status Kontrak (BERJALAN, SELESAI, or label)
            if (!empty($filters['status_kontrak']) && strtolower((string) $filters['status_kontrak']) !== 'all') {
                $statusQuery = strtoupper(trim((string) $filters['status_kontrak']));
                if ($statusQuery === 'KONTRAK BERJALAN') {
                    $statusQuery = 'BERJALAN';
                } elseif ($statusQuery === 'KONTRAK SELESAI') {
                    $statusQuery = 'SELESAI';
                }
                if (strtoupper((string) ($contract['status_kontrak'] ?? 'BERJALAN')) !== $statusQuery) {
                    return false;
                }
            }

            // Filter by SP/PO Status
            if (!empty($filters['sp_po'])) {
                $spPo = strtoupper(trim($filters['sp_po']));
                if (strtoupper($contract['sp_po'] ?? '') !== $spPo) {
                    return false;
                }
            }

            // Filter by minimum revenue
            if (isset($filters['revenue_min']) && is_numeric($filters['revenue_min'])) {
                if ((int) ($contract['revenue'] ?? 0) < (int) $filters['revenue_min']) {
                    return false;
                }
            }

            // Filter by maximum revenue
            if (isset($filters['revenue_max']) && is_numeric($filters['revenue_max'])) {
                if ((int) ($contract['revenue'] ?? 0) > (int) $filters['revenue_max']) {
                    return false;
                }
            }

            return true;
        }));
    }

    /**
     * Combined search and filter execution.
     *
     * @param string $query
     * @param array<string, mixed> $filters
     * @param array<int, array<string, mixed>>|null $contracts
     * @return array<int, array<string, mixed>>
     */
    public function searchAndFilter(?string $query = '', array $filters = [], ?array $contracts = null): array
    {
        $results = $this->searchContracts($query, $contracts);
        return $this->filterContracts($filters, $results);
    }

    /**
     * Group contracts by Stage for Pipeline / Kanban view (PRD FR-04).
     *
     * @param array<int, array<string, mixed>>|null $contracts
     * @return array<string, array<string, mixed>>
     */
    public function getContractsByStage(?array $contracts = null): array
    {
        $contracts = $contracts !== null ? $contracts : $this->getAllContracts();

        $grouped = [];
        foreach (Stage::cases() as $case) {
            $grouped[$case->value] = [
                'stage' => $case->value,
                'label' => $case->label(),
                'full_label' => $case->fullLabel(),
                'count' => 0,
                'total_revenue' => 0,
                'total_revenue_formatted' => $this->transformer->formatCurrency(0),
                'contracts' => [],
            ];
        }

        foreach ($contracts as $contract) {
            $stage = $contract['stage'] ?? 'F0';
            $revenue = (int) ($contract['revenue'] ?? 0);

            if (isset($grouped[$stage])) {
                $grouped[$stage]['count']++;
                $grouped[$stage]['total_revenue'] += $revenue;
                $grouped[$stage]['contracts'][] = $contract;
            }
        }

        foreach ($grouped as $key => $data) {
            $grouped[$key]['total_revenue_formatted'] = $this->transformer->formatCurrency($data['total_revenue']);
        }

        return $grouped;
    }

    /**
     * Sort contracts by a specific key.
     *
     * @param array<int, array<string, mixed>> $contracts
     * @param string $sortBy
     * @param string $direction ('asc' or 'desc')
     * @return array<int, array<string, mixed>>
     */
    public function sortContracts(array $contracts, string $sortBy = 'lop', string $direction = 'asc'): array
    {
        $direction = strtolower($direction) === 'desc' ? 'desc' : 'asc';

        usort($contracts, function ($a, $b) use ($sortBy, $direction) {
            $valA = $a[$sortBy] ?? null;
            $valB = $b[$sortBy] ?? null;

            if ($valA === $valB) {
                return 0;
            }

            if ($valA === null) {
                return $direction === 'asc' ? 1 : -1;
            }
            if ($valB === null) {
                return $direction === 'asc' ? -1 : 1;
            }

            $comparison = is_numeric($valA) && is_numeric($valB)
                ? ($valA <=> $valB)
                : strnatcasecmp((string) $valA, (string) $valB);

            return $direction === 'asc' ? $comparison : -$comparison;
        });

        return $contracts;
    }

    /**
     * Validate contract data against business rules.
     *
     * @param array<string, mixed> $data
     * @param bool $isUpdate
     * @return array<string, mixed>
     * @throws ValidationException
     */
    protected function validateContractData(array $data, bool $isUpdate = false): array
    {
        // Aliases and fallbacks
        if (empty($data['customer']) && !empty($data['judul_proyek'])) {
            $data['customer'] = $data['judul_proyek'];
        } elseif (empty($data['customer']) && !empty($data['nama_gc'])) {
            $data['customer'] = $data['nama_gc'];
        } elseif (empty($data['customer']) && !empty($data['satker'])) {
            $data['customer'] = $data['satker'];
        }

        if (empty($data['judul_proyek']) && !empty($data['customer'])) {
            $data['judul_proyek'] = $data['customer'];
        }

        if (empty($data['service']) && !empty($data['deskripsi_layanan'])) {
            $data['service'] = $data['deskripsi_layanan'];
        }

        if ((!isset($data['revenue']) || $data['revenue'] === '') && isset($data['nilai_realisasi_win'])) {
            $data['revenue'] = $data['nilai_realisasi_win'];
        }

        // Auto-calculate billcomp_percentage from billcomp_nominal if supplied
        if (isset($data['billcomp_nominal']) && $data['billcomp_nominal'] !== '') {
            $nominal = $this->transformer->parseCurrency($data['billcomp_nominal']);
            $rev = $this->transformer->parseCurrency($data['revenue'] ?? 0);
            if ($rev > 0) {
                $data['billcomp_percentage'] = min(100, max(0, (int) round(($nominal / $rev) * 100)));
            } else {
                $data['billcomp_percentage'] = 0;
            }
        }

        $rules = [
            'id_mytens' => 'nullable|string|max:100',
            'tahun' => 'nullable|string|max:20',
            'lop' => $isUpdate ? 'nullable|string' : 'required|string|max:100',
            'contract_number' => 'nullable|string|max:100',
            'nama_gc' => 'nullable|string|max:255',
            'customer' => 'required|string|max:255',
            'judul_proyek' => 'nullable|string|max:500',
            'satker' => 'nullable|string|max:255',
            'service' => 'required|string|max:255',
            'deskripsi_layanan' => 'nullable|string|max:255',
            'stage' => 'required|string|in:F0,F1,F2,F3,F4',
            'estimasi_nilai_proyek' => 'nullable',
            'revenue' => 'required',
            'nilai_realisasi_win' => 'nullable',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date',
            'durasi_bulan' => 'nullable',
            'estimasi_durasi_bulan' => 'nullable',
            'estimasi_bulan_bc' => 'nullable|string|max:100',
            'nilai_bc' => 'nullable',
            'sp_po' => 'nullable|string|in:AVAILABLE,MISSING',
            'invoice_status' => 'nullable|string|in:UNBILLED,ISSUED,PAID',
            'billcomp_status' => 'nullable|string|in:NOT_COMPLETE,PARTIAL,COMPLETED',
            'billcomp_percentage' => 'nullable',
            'billcomp_nominal' => 'nullable',
            'document_reference' => 'nullable|string',
            'status_kontrak' => 'nullable|string|in:BERJALAN,SELESAI,Kontrak Berjalan,Kontrak Selesai',
        ];

        $validator = Validator::make($data, $rules);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        return $validator->validated();
    }
}

<?php

namespace App\Http\Controllers;

use App\Services\ContractService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class ContractController extends Controller
{
    /**
     * Whitelist of permitted contract fields to prevent mass assignment.
     */
    public const ALLOWED_FIELDS = [
        'id_mytens',
        'tahun',
        'lop',
        'contract_number',
        'nama_gc',
        'satker',
        'judul_proyek',
        'customer',
        'service',
        'deskripsi_layanan',
        'stage',
        'estimasi_nilai_proyek',
        'revenue',
        'nilai_realisasi_win',
        'start_date',
        'end_date',
        'durasi_bulan',
        'estimasi_durasi_bulan',
        'estimasi_bulan_bc',
        'nilai_bc',
        'sp_po',
        'invoice_status',
        'billcomp_status',
        'billcomp_percentage',
        'billcomp_nominal',
        'document_reference',
        'status_kontrak',
    ];

    public function __construct(
        protected ContractService $contractService
    ) {}

    /**
     * Display the consolidated Contracts & Pipeline management page.
     */
    public function index(Request $request): View
    {
        $user = $request->user();
        $errorMessage = null;
        $allContracts = [];
        $contracts = [];
        $stages = [];

        try {
            $allContracts = $this->contractService->getAllContracts();
            // Search & Filter execution
            $query = (string) ($request->input('q') ?? '');
            $filters = $request->only([
                'tahun',
                'nama_gc',
                'stage',
                'satker',
                'service',
                'invoice_status',
                'billcomp_status',
                'expiration_status',
                'sp_po',
                'status_kontrak',
                'revenue_min',
                'revenue_max',
            ]);

            // Filter out empty filter values and "all" placeholders
            $filters = array_filter($filters, fn($val) => $val !== null && trim((string)$val) !== '' && strtolower(trim((string)$val)) !== 'all');

            $filteredContracts = $this->contractService->searchAndFilter($query, $filters, $allContracts);
            $stages = $this->contractService->getContractsByStage($filteredContracts);

            // Sorting
            $sortBy = $request->input('sort_by', 'lop');
            $sortDir = $request->input('sort_dir', 'asc');
            $contracts = $this->contractService->sortContracts($filteredContracts, $sortBy, $sortDir);
        } catch (\Throwable $e) {
            Log::error('Contracts index retrieval error: ' . $e->getMessage(), [
                'user_id' => $user->id,
            ]);
            $errorMessage = 'Gagal memuat data kontrak dari Google Sheets. Silakan periksa koneksi atau coba beberapa saat lagi.';
            $allContracts = [];
            $contracts = [];
            $stages = $this->contractService->getContractsByStage([]);
        }

        // Distinct lists for filter dropdown options
        $availableYears = collect($allContracts)->pluck('tahun')->filter()->unique()->sortDesc()->values();
        $availableGCs = collect($allContracts)->pluck('nama_gc')->filter()->unique()->sort()->values();
        $availableSatkers = collect($allContracts)->pluck('satker')->filter()->unique()->sort()->values();
        $availableServices = collect($allContracts)->pluck('service')->filter()->unique()->sort()->values();

        // Calculate truly active data filters (excluding view/page navigation params)
        $activeDataFilters = array_filter(
            $request->only([
                'q',
                'tahun',
                'nama_gc',
                'stage',
                'satker',
                'service',
                'invoice_status',
                'billcomp_status',
                'expiration_status',
                'sp_po',
                'status_kontrak',
                'revenue_min',
                'revenue_max',
            ]),
            fn($val) => $val !== null && trim((string)$val) !== '' && strtolower(trim((string)$val)) !== 'all'
        );

        return view('contracts.index', [
            'user' => $user,
            'contracts' => $contracts,
            'allContracts' => $allContracts,
            'stages' => $stages,
            'availableYears' => $availableYears,
            'availableGCs' => $availableGCs,
            'availableSatkers' => $availableSatkers,
            'availableServices' => $availableServices,
            'filters' => array_merge(['q' => $request->input('q', '')], $request->all()),
            'activeFilters' => $activeDataFilters,
            'activeFilterCount' => count($activeDataFilters),
            'currentView' => in_array($request->input('view'), ['table', 'kanban']) ? $request->input('view') : 'table',
            'errorMessage' => $errorMessage,
        ]);
    }

    /**
     * Display the specified contract detail (PRD FR-06).
     */
    public function show(string $lop): View|RedirectResponse
    {
        try {
            $contract = $this->contractService->findByLop($lop);

            if (!$contract) {
                return redirect()->route('dashboard')->with('error', "Kontrak dengan LOP '{$lop}' tidak ditemukan.");
            }

            return view('contracts.show', [
                'contract' => $contract,
            ]);
        } catch (\Throwable $e) {
            Log::error("Failed to load contract detail for LOP '{$lop}': " . $e->getMessage(), [
                'lop' => $lop,
            ]);
            return redirect()->route('dashboard')->with('error', 'Gagal memuat detail kontrak dari Google Sheets. Silakan coba beberapa saat lagi.');
        }
    }

    /**
     * Show the form for creating a new contract (PRD FR-07).
     */
    public function create(): View
    {
        return view('contracts.create');
    }

    /**
     * Store a newly created contract in Google Sheets (PRD FR-07).
     */
    public function store(Request $request): RedirectResponse
    {
        try {
            $created = $this->contractService->createContract($request->only(self::ALLOWED_FIELDS), $request->user()->id);

            return redirect()
                ->route('contracts.show', $created['lop'])
                ->with('status', "Kontrak dengan LOP '{$created['lop']}' berhasil ditambahkan ke Google Sheets.");
        } catch (ValidationException $e) {
            throw $e;
        } catch (\Throwable $e) {
            Log::error('Failed to create contract in Google Sheets: ' . $e->getMessage(), [
                'user_id' => $request->user()->id,
                'data' => \App\Helpers\LogSanitizer::sanitize($request->only(self::ALLOWED_FIELDS)),
            ]);

            return back()
                ->withInput()
                ->with('error', 'Gagal menyimpan kontrak ke Google Sheets. Silakan periksa koneksi dan coba lagi.');
        }
    }

    /**
     * Show the form for editing the specified contract (PRD FR-07).
     */
    public function edit(string $lop): View|RedirectResponse
    {
        try {
            $contract = $this->contractService->findByLop($lop);

            if (!$contract) {
                return redirect()->route('dashboard')->with('error', "Kontrak dengan LOP '{$lop}' tidak ditemukan.");
            }

            return view('contracts.edit', [
                'contract' => $contract,
            ]);
        } catch (\Throwable $e) {
            Log::error("Failed to load contract for editing LOP '{$lop}': " . $e->getMessage(), [
                'lop' => $lop,
            ]);
            return redirect()->route('dashboard')->with('error', 'Gagal memuat data kontrak untuk diedit. Silakan coba beberapa saat lagi.');
        }
    }

    /**
     * Update the specified contract in Google Sheets (PRD FR-07).
     */
    public function update(Request $request, string $lop): RedirectResponse
    {
        try {
            $updated = $this->contractService->updateContract($lop, $request->only(self::ALLOWED_FIELDS), $request->user()->id);

            return redirect()
                ->route('contracts.show', $lop)
                ->with('status', "Data kontrak '{$lop}' berhasil diperbarui di Google Sheets.");
        } catch (ValidationException $e) {
            throw $e;
        } catch (\Throwable $e) {
            Log::error("Failed to update contract '{$lop}' in Google Sheets: " . $e->getMessage(), [
                'user_id' => $request->user()->id,
                'lop' => $lop,
            ]);

            return back()
                ->withInput()
                ->with('error', 'Gagal memperbarui data kontrak di Google Sheets. Silakan periksa koneksi dan coba lagi.');
        }
    }

    /**
     * Mark an overdue contract as completed manually by AM.
     */
    public function complete(Request $request, string $lop): RedirectResponse
    {
        try {
            $updated = $this->contractService->completeContract($lop, $request->user()->id);

            return redirect()
                ->route('contracts.show', $lop)
                ->with('status', "Kontrak '{$lop}' telah berhasil diselesaikan (Status: Kontrak Selesai).");
        } catch (\Throwable $e) {
            Log::error("Failed to mark contract '{$lop}' as completed: " . $e->getMessage(), [
                'user_id' => $request->user()->id,
                'lop' => $lop,
            ]);

            return back()->with('error', 'Gagal menyelesaikan kontrak di Google Sheets. Silakan coba lagi.');
        }
    }
}

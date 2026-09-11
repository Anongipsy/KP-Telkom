<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use App\Services\ContractService;
use Exception;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __construct(
        protected ContractService $contractService
    ) {}

    /**
     * Display the main enterprise dashboard.
     */
    public function index(Request $request): View
    {
        $user = $request->user();
        $errorMessage = null;
        $allContracts = [];
        $contracts = [];
        $kpi = [];
        $stages = [];

        $selectedYear = trim((string) $request->input('tahun', ''));
        $selectedGc = trim((string) $request->input('nama_gc', ''));

        try {
            // Fetch all contracts through ContractService (Google Sheets = Source of Truth)
            $allContracts = $this->contractService->getAllContracts();

            // Prepare filters
            $filters = [];
            if ($selectedYear !== '' && strtolower($selectedYear) !== 'all') {
                $filters['tahun'] = $selectedYear;
            }
            if ($selectedGc !== '' && strtolower($selectedGc) !== 'all') {
                $filters['nama_gc'] = $selectedGc;
            }

            // Filter contracts based on selection (or all if no filter)
            $contracts = !empty($filters)
                ? $this->contractService->filterContracts($filters, $allContracts)
                : $allContracts;

            $kpi = $this->contractService->getKpiSummary($contracts);
            $stages = $this->contractService->getContractsByStage($contracts);
        } catch (\Throwable $e) {
            Log::error('Dashboard data retrieval error: ' . $e->getMessage(), [
                'user_id' => $user->id,
            ]);
            $errorMessage = 'Gagal memuat data dari Google Sheets. Silakan periksa koneksi atau coba beberapa saat lagi.';

            // Provide safe fallback structure
            $allContracts = [];
            $contracts = [];
            $kpi = $this->contractService->getKpiSummary([]);
            $stages = $this->contractService->getContractsByStage([]);
        }

        // Distinct years for filter dropdown (sorted descending)
        $availableYears = collect($allContracts)
            ->map(function ($c) {
                $year = trim((string) ($c['tahun'] ?? ''));
                if ($year === '' && !empty($c['start_date'])) {
                    $year = substr((string) $c['start_date'], 0, 4);
                }
                return $year;
            })
            ->filter()
            ->unique()
            ->sortDesc()
            ->values();

        // Distinct Group Companies for filter dropdown (sorted alphabetically)
        $availableGCs = collect($allContracts)
            ->pluck('nama_gc')
            ->map(fn($gc) => trim((string) $gc))
            ->filter()
            ->unique()
            ->sort()
            ->values();

        // Fetch unread notifications for this user
        $notifications = Notification::where('user_id', $user->id)
            ->unread()
            ->latest()
            ->take(10)
            ->get();

        $unreadCount = Notification::where('user_id', $user->id)
            ->unread()
            ->count();

        // Categorize contracts for Expiration Summary section (using filtered contracts, excluding completed contracts)
        $isNotCompleted = fn($c) => !($c['is_completed'] ?? false)
            && strtoupper((string) ($c['status_kontrak'] ?? 'BERJALAN')) !== 'SELESAI'
            && !str_contains(strtoupper((string) ($c['status_kontrak'] ?? '')), 'SELESAI');

        $activeContracts = array_values(array_filter($contracts, fn($c) => ($c['expiration_status'] ?? '') === 'ACTIVE' && $isNotCompleted($c)));
        $expiringSoonContracts = array_values(array_filter($contracts, fn($c) => ($c['expiration_status'] ?? '') === 'EXPIRING_SOON' && $isNotCompleted($c)));
        $overdueContracts = array_values(array_filter($contracts, fn($c) => ($c['expiration_status'] ?? '') === 'OVERDUE' && $isNotCompleted($c)));

        // Prepare data for Stage Pipeline Donut Chart
        $stageChartData = [
            'labels' => collect($stages)->map(fn($s) => $s['stage'] . ' - ' . $s['label'])->values()->all(),
            'stages' => collect($stages)->pluck('stage')->values()->all(),
            'counts' => collect($stages)->pluck('count')->values()->all(),
            'revenues' => collect($stages)->pluck('total_revenue')->values()->all(),
            'revenues_formatted' => collect($stages)->pluck('total_revenue_formatted')->values()->all(),
        ];

        // Prepare data for Revenue per GC Bar Chart
        $gcSummary = collect($contracts)
            ->groupBy(function ($c) {
                $gc = trim((string) ($c['nama_gc'] ?? ''));
                return $gc !== '' ? $gc : 'Non-GC / Satker Lain';
            })
            ->map(function ($group, $gc) {
                $revenue = $group->sum(fn($c) => (int) ($c['revenue'] ?? 0));
                return [
                    'gc' => $gc,
                    'revenue' => $revenue,
                    'revenue_formatted' => 'Rp ' . number_format($revenue, 0, ',', '.'),
                    'count' => $group->count(),
                ];
            })
            ->sortByDesc('revenue')
            ->values();

        $gcChartData = [
            'labels' => $gcSummary->pluck('gc')->all(),
            'revenues' => $gcSummary->pluck('revenue')->all(),
            'revenues_formatted' => $gcSummary->pluck('revenue_formatted')->all(),
            'counts' => $gcSummary->pluck('count')->all(),
        ];

        return view('dashboard.index', [
            'user' => $user,
            'contracts' => $contracts,
            'allContracts' => $allContracts,
            'kpi' => $kpi,
            'stages' => $stages,
            'stageChartData' => $stageChartData,
            'gcChartData' => $gcChartData,
            'availableYears' => $availableYears,
            'availableGCs' => $availableGCs,
            'selectedYear' => $selectedYear,
            'selectedGc' => $selectedGc,
            'totalUnfilteredCount' => count($allContracts),
            'notifications' => $notifications,
            'unreadCount' => $unreadCount,
            'activeContracts' => $activeContracts,
            'expiringSoonContracts' => $expiringSoonContracts,
            'overdueContracts' => $overdueContracts,
            'errorMessage' => $errorMessage,
        ]);
    }

    /**
     * Force refresh contract data from Google Sheets (Cache invalidation).
     */
    public function refresh(Request $request): RedirectResponse
    {
        try {
            $this->contractService->getAllContracts(forceRefresh: true);
            return back()->with('status', 'Data kontrak berhasil diperbarui langsung dari Google Sheets.');
        } catch (\Throwable $e) {
            Log::error('Dashboard force refresh failed: ' . $e->getMessage(), [
                'user_id' => $request->user()->id,
            ]);
            return back()->with('error', 'Gagal menyegarkan data dari Google Sheets. Silakan coba beberapa saat lagi.');
        }
    }
}

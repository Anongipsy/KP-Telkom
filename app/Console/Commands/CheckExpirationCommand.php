<?php

namespace App\Console\Commands;

use App\Models\AuditLog;
use App\Models\SyncLog;
use App\Services\ContractService;
use App\Services\NotificationService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * CheckExpirationCommand — PRD FR-10, FR-11, FR-16, FR-17
 *
 * Checks contract expiration dates against Google Sheets data and generates early warning alerts.
 */
class CheckExpirationCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'contracts:check-expiration
                            {--user= : Target a specific user ID}
                            {--dry-run : Simulate execution without writing to database}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check contract expiration dates and generate early warning alerts (PRD FR-10, FR-11)';

    /**
     * Execute the console command.
     */
    public function handle(ContractService $contractService, NotificationService $notificationService): int
    {
        $targetUserId = $this->option('user') ? (int) $this->option('user') : null;
        $isDryRun = (bool) $this->option('dry-run');

        $startedAt = Carbon::now();

        $this->info('=== [Telkom B2B] Contract Expiration Early Warning System ===');
        if ($isDryRun) {
            $this->warn('Mode: DRY-RUN (Tidak ada data yang disimpan ke database)');
        }

        $this->line('Mengambil data kontrak terbaru dari Google Sheets...');

        try {
            $contracts = $contractService->getAllContracts();
            $this->info("Berhasil memuat " . count($contracts) . " kontrak.");

            $this->line('Menganalisis tanggal berakhir kontrak dan memproses notifikasi...');
            $summary = $notificationService->processContracts($contracts, $targetUserId, $isDryRun);

            // Display Results Summary Table
            $this->newLine();
            $this->table(
                ['Metrik', 'Nilai'],
                [
                    ['Total Kontrak Dievaluasi', $summary['contracts_count']],
                    ['Target Pengguna (AM)', $summary['users_count']],
                    ['Notifikasi Baru Dibuat', $summary['created']],
                    ['Notifikasi Diperbarui (H-x / Overdue)', $summary['updated']],
                    ['Notifikasi Usang Dibersihkan (Renewal)', $summary['cleaned']],
                ]
            );

            // Detailed Breakdown
            if (!empty($summary['details']) && $this->getOutput()->isVerbose()) {
                $this->newLine();
                $this->line('<comment>Detail Perubahan Notifikasi:</comment>');
                $rows = array_map(function ($d) {
                    return [
                        $d['user_id'] ?? '-',
                        $d['lop'] ?? '-',
                        $d['alert_type'] ?? '-',
                        $d['days_remaining'] ?? '-',
                        strtoupper($d['action'] ?? '-'),
                    ];
                }, $summary['details']);

                $this->table(['User ID', 'LOP', 'Tipe Alert', 'Sisa Hari', 'Aksi'], $rows);
            }

            // Record SyncLog & AuditLog if not dry-run
            if (!$isDryRun) {
                SyncLog::record(
                    syncType: 'EXPIRATION_CHECK',
                    direction: 'SHEETS_TO_APP',
                    status: 'SUCCESS',
                    recordsProcessed: $summary['contracts_count'],
                    startedAt: $startedAt,
                    completedAt: Carbon::now()
                );

                AuditLog::record(
                    action: 'expiration_check_completed',
                    userId: $targetUserId,
                    targetType: 'system_task',
                    targetReference: 'contracts:check-expiration',
                    metadata: [
                        'contracts_count' => $summary['contracts_count'],
                        'created' => $summary['created'],
                        'updated' => $summary['updated'],
                        'cleaned' => $summary['cleaned'],
                    ]
                );
            }

            $this->newLine();
            $this->info('Pengecekan masa berlaku kontrak selesai dengan sukses.');

            return Command::SUCCESS;
        } catch (Throwable $e) {
            $this->error('Gagal menjalankan pengecekan masa berlaku kontrak: ' . $e->getMessage());
            Log::error('CheckExpirationCommand failed: ' . $e->getMessage(), [
                'exception' => $e,
            ]);

            if (!$isDryRun) {
                SyncLog::record(
                    syncType: 'EXPIRATION_CHECK',
                    direction: 'SHEETS_TO_APP',
                    status: 'FAILED',
                    recordsProcessed: 0,
                    errorMessage: $e->getMessage(),
                    startedAt: $startedAt,
                    completedAt: Carbon::now()
                );
            }

            return Command::FAILURE;
        }
    }
}

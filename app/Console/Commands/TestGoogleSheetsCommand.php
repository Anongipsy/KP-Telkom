<?php

namespace App\Console\Commands;

use App\Services\ContractService;
use App\Services\GoogleSheetsService;
use Illuminate\Console\Command;
use Throwable;

class TestGoogleSheetsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sheets:test {--refresh : Force refresh cache}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test connection to Google Sheets API and display fetched contracts';

    /**
     * Execute the console command.
     */
    public function handle(GoogleSheetsService $sheetsService, ContractService $contractService): int
    {
        $this->info('=== Testing Google Sheets API Integration ===');

        $spreadsheetId = config('google.sheets.spreadsheet_id');
        $clientEmail = config('google.client_email');

        $this->line("Spreadsheet ID: <comment>{$spreadsheetId}</comment>");
        $this->line("Service Account: <comment>{$clientEmail}</comment>");

        if (empty($spreadsheetId) || empty($clientEmail)) {
            $this->error('Google Sheets credentials are not configured in .env');
            return Command::FAILURE;
        }

        try {
            $this->info('Connecting to Google Sheets API...');

            $forceRefresh = $this->option('refresh');
            $contracts = $contractService->getAllContracts($forceRefresh);

            $this->info('Successfully connected and retrieved contracts!');
            $this->line("Total Contracts Found: <comment>" . count($contracts) . "</comment>");

            if (!empty($contracts)) {
                $rows = array_map(function ($c) {
                    return [
                        $c['lop'] ?? '-',
                        $c['customer'] ?? '-',
                        $c['service'] ?? '-',
                        $c['stage'] ?? '-',
                        $c['revenue_formatted'] ?? '-',
                        $c['expiration_status'] ?? '-',
                        $c['days_remaining'] !== null ? $c['days_remaining'] . ' days' : '-',
                    ];
                }, array_slice($contracts, 0, 10));

                $this->table(
                    ['LOP', 'Customer', 'Service', 'Stage', 'Revenue', 'Status', 'Days Remaining'],
                    $rows
                );

                if (count($contracts) > 10) {
                    $this->line("... and " . (count($contracts) - 10) . " more contracts.");
                }

                $kpi = $contractService->getKpiSummary($contracts);
                $this->newLine();
                $this->info('--- KPI Summary ---');
                $this->line("Total Pipeline Revenue: <comment>{$kpi['total_pipeline_revenue_formatted']}</comment>");
                $this->line("Total Realized Revenue: <comment>{$kpi['total_realized_revenue_formatted']} ({$kpi['realized_percentage']}%)</comment>");
                $this->line("Active: <comment>{$kpi['active_contracts']}</comment> | Expiring Soon: <comment>{$kpi['expiring_soon_contracts']}</comment> | Overdue: <comment>{$kpi['overdue_contracts']}</comment>");
            } else {
                $this->warn('Spreadsheet is empty or has no data rows yet.');
            }

            return Command::SUCCESS;
        } catch (Throwable $e) {
            $this->newLine();
            $this->error('=== Google Sheets Connection Error ===');
            $this->line("<fg=red>Exception Class:</> " . get_class($e));
            $this->line("<fg=red>Exception Message:</> " . $e->getMessage());
            $this->line("<fg=red>Error Code:</> " . $e->getCode());

            if ($e instanceof \Google\Service\Exception) {
                $this->line("<fg=red>HTTP Status Code:</> " . $e->getCode());
                $errors = $e->getErrors();
                if (!empty($errors)) {
                    $this->line("<fg=red>Google API Errors:</>");
                    foreach ($errors as $err) {
                        $domain = $err['domain'] ?? '-';
                        $reason = $err['reason'] ?? '-';
                        $message = $err['message'] ?? '-';
                        $this->line("  - Domain: <comment>{$domain}</comment> | Reason: <comment>{$reason}</comment> | Message: <comment>{$message}</comment>");
                    }
                }
            }

            // Inspect nested previous exceptions
            $current = $e->getPrevious();
            $depth = 1;
            while ($current !== null) {
                $this->newLine();
                $this->line("<fg=yellow>[Underlying Cause #{$depth}]</>");
                $this->line("  <fg=yellow>Class:</> " . get_class($current));
                $this->line("  <fg=yellow>Message:</> " . $current->getMessage());
                $this->line("  <fg=yellow>Code:</> " . $current->getCode());

                if ($current instanceof \Google\Service\Exception) {
                    $this->line("  <fg=yellow>HTTP Status:</> " . $current->getCode());
                    $errors = $current->getErrors();
                    if (!empty($errors)) {
                        $this->line("  <fg=yellow>Google API Error Reasons:</>");
                        foreach ($errors as $err) {
                            $domain = $err['domain'] ?? '-';
                            $reason = $err['reason'] ?? '-';
                            $msg = $err['message'] ?? '-';
                            $this->line("    * [{$domain}] {$reason}: {$msg}");
                        }
                    }
                }

                $current = $current->getPrevious();
                $depth++;
            }

            return Command::FAILURE;
        }
    }
}

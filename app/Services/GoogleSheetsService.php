<?php

namespace App\Services;

use App\Models\SyncLog;
use Google\Client as GoogleClient;
use Google\Service\Sheets as GoogleSheets;
use Google\Service\Sheets\ValueRange;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

/**
 * GoogleSheetsService — PRD Section 6.1, 10, 11, 14, 15, FR-07, FR-08, FR-17
 *
 * Core service for reading and writing contract data to Google Sheets API v4.
 * Google Sheets is the Single Source of Truth.
 */
class GoogleSheetsService
{
    public const CACHE_KEY_ALL_CONTRACTS = 'google_sheets_contracts_all';
    public const CACHE_KEY_HEADERS = 'google_sheets_headers';

    protected ?GoogleSheets $sheetsService = null;
    protected DataTransformer $transformer;

    public function __construct(?DataTransformer $transformer = null)
    {
        $this->transformer = $transformer ?: new DataTransformer();
    }

    /**
     * Set a custom Google Sheets client (useful for unit/feature testing with mocks).
     */
    public function setSheetsService(GoogleSheets $sheetsService): self
    {
        $this->sheetsService = $sheetsService;
        return $this;
    }

    /**
     * Get or initialize the authenticated Google Sheets service.
     */
    public function getSheetsService(): GoogleSheets
    {
        if ($this->sheetsService !== null) {
            return $this->sheetsService;
        }

        $this->sheetsService = $this->createAuthenticatedSheetsService();
        return $this->sheetsService;
    }

    /**
     * Create authenticated Google Sheets client using Service Account credentials.
     */
    protected function createAuthenticatedSheetsService(): GoogleSheets
    {
        $clientEmail = config('google.client_email');
        $privateKey = config('google.private_key');
        $projectId = config('google.project_id');

        if (empty($clientEmail) || empty($privateKey)) {
            throw new RuntimeException('Google Cloud Service Account credentials are not configured.');
        }

        // Normalize private key (replace escaped literal \n with real newlines)
        $formattedPrivateKey = str_replace(['\n', '\r'], ["\n", ''], $privateKey);

        $guzzleOptions = [];
        if (app()->environment('local', 'testing')) {
            $guzzleOptions['verify'] = false;
        }

        $httpClient = new \GuzzleHttp\Client($guzzleOptions);
        \Google\Auth\HttpHandler\HttpClientCache::setHttpClient($httpClient);

        $authConfig = [
            'type' => 'service_account',
            'project_id' => $projectId,
            'client_email' => $clientEmail,
            'client_id' => $clientEmail,
            'private_key' => $formattedPrivateKey,
        ];

        $client = new GoogleClient();
        $client->setApplicationName(config('app.name', 'Telkom B2B Monitoring'));
        $client->setHttpClient($httpClient);
        $client->setScopes([GoogleSheets::SPREADSHEETS]);
        $client->setAuthConfig($authConfig);

        return new GoogleSheets($client);
    }

    /**
     * Get spreadsheet ID from config.
     */
    public function getSpreadsheetId(): string
    {
        $id = config('google.sheets.spreadsheet_id');
        if (empty($id)) {
            throw new RuntimeException('Google Sheets Spreadsheet ID is not configured.');
        }
        return $id;
    }

    /**
     * Get sheet range from config (e.g. "Contracts!A:N").
     */
    public function getRange(): string
    {
        return config('google.sheets.range', 'Contracts!A:V');
    }

    /**
     * Get sheet name from config (e.g. "Contracts").
     */
    public function getSheetName(): string
    {
        return config('google.sheets.sheet_name', 'Contracts');
    }

    /**
     * Get cache TTL in seconds.
     */
    public function getCacheTtl(): int
    {
        return (int) config('google.sheets.cache_ttl', 300);
    }

    /**
     * Read raw values from spreadsheet.
     *
     * @param string|null $range
     * @return array<int, array<int, mixed>>
     */
    public function getRawValues(?string $range = null): array
    {
        $spreadsheetId = $this->getSpreadsheetId();
        $range = $range ?: $this->getRange();

        try {
            $response = $this->getSheetsService()->spreadsheets_values->get($spreadsheetId, $range);
            return $response->getValues() ?: [];
        } catch (Throwable $e) {
            $this->logError('Failed to read spreadsheet values', $e);
            throw new RuntimeException("Unable to retrieve contract data from Google Sheets: [" . get_class($e) . "] " . $e->getMessage(), (int) $e->getCode(), $e);
        }
    }

    /**
     * Get headers and all transformed contracts (with caching).
     *
     * @param bool $forceRefresh
     * @return array<int, array<string, mixed>>
     */
    public function getAllContracts(bool $forceRefresh = false): array
    {
        if ($forceRefresh) {
            $this->clearCache();
        }

        $cacheTtl = $this->getCacheTtl();

        return Cache::remember(self::CACHE_KEY_ALL_CONTRACTS, $cacheTtl, function () {
            $startedAt = now();
            $rawValues = $this->getRawValues();

            if (empty($rawValues)) {
                return [];
            }

            $headerRowIndex = max(1, (int) config('google.sheets.header_row', 1)) - 1;
            $headers = $rawValues[$headerRowIndex] ?? DataTransformer::STANDARD_HEADERS;

            // Cache headers as well
            Cache::put(self::CACHE_KEY_HEADERS, $headers, $this->getCacheTtl());

            $dataRows = array_slice($rawValues, $headerRowIndex + 1);
            $startRowNumber = $headerRowIndex + 2; // 1-based row index in sheet

            $transformed = $this->transformer->transformAll($headers, $dataRows, $startRowNumber);

            // Log read sync operation
            SyncLog::create([
                'user_id' => auth()->id(),
                'sync_type' => 'contracts',
                'direction' => 'SHEETS_TO_APP',
                'status' => 'success',
                'records_processed' => count($transformed),
                'started_at' => $startedAt,
                'completed_at' => now(),
            ]);

            return $transformed;
        });
    }

    /**
     * Get cached or fresh header row.
     *
     * @return array<int, string>
     */
    public function getHeaders(): array
    {
        return Cache::remember(self::CACHE_KEY_HEADERS, $this->getCacheTtl(), function () {
            $rawValues = $this->getRawValues();
            $headerRowIndex = max(1, (int) config('google.sheets.header_row', 1)) - 1;
            return $rawValues[$headerRowIndex] ?? DataTransformer::STANDARD_HEADERS;
        });
    }

    /**
     * Find a contract by LOP identifier.
     *
     * @param string $lop
     * @return array<string, mixed>|null
     */
    public function findByLop(string $lop): ?array
    {
        $contracts = $this->getAllContracts();
        $normalizedSearchLop = trim(strtolower($lop));

        foreach ($contracts as $contract) {
            if (trim(strtolower($contract['lop'] ?? '')) === $normalizedSearchLop) {
                return $contract;
            }
        }

        return null;
    }

    /**
     * Update an existing contract by LOP.
     *
     * @param string $lop
     * @param array<string, mixed> $data
     * @param int|null $userId
     * @return array<string, mixed>
     */
    public function updateByLop(string $lop, array $data, ?int $userId = null): array
    {
        $startedAt = now();
        $existing = $this->findByLop($lop);

        if (!$existing) {
            throw new RuntimeException("Contract with LOP '{$lop}' not found in Google Sheets.");
        }

        $rowIndex = $existing['_row_index'] ?? null;
        if (!$rowIndex) {
            throw new RuntimeException("Cannot determine row position for LOP '{$lop}'.");
        }

        // Merge updated attributes onto existing contract
        $merged = array_merge($existing, $data, ['lop' => $lop]);

        $headers = $this->getHeaders();
        $rowValues = $this->transformer->toSpreadsheetRow($merged, $headers);

        $sheetName = $this->getSheetName();
        $lastColumnLetter = $this->getColumnLetter(count($headers));
        $targetRange = "{$sheetName}!A{$rowIndex}:{$lastColumnLetter}{$rowIndex}";

        try {
            $valueRange = new ValueRange();
            $valueRange->setValues([$rowValues]);

            $this->getSheetsService()->spreadsheets_values->update(
                $this->getSpreadsheetId(),
                $targetRange,
                $valueRange,
                ['valueInputOption' => 'USER_ENTERED']
            );

            // Invalidate cache
            $this->clearCache();

            SyncLog::create([
                'user_id' => $userId ?: auth()->id(),
                'sync_type' => 'contracts',
                'direction' => 'APP_TO_SHEETS',
                'status' => 'success',
                'records_processed' => 1,
                'started_at' => $startedAt,
                'completed_at' => now(),
            ]);

            return $this->transformer->transformRow($headers, $rowValues, $rowIndex);
        } catch (Throwable $e) {
            $this->logError("Failed to update contract LOP '{$lop}' in Google Sheets", $e);

            SyncLog::create([
                'user_id' => $userId ?: auth()->id(),
                'sync_type' => 'contracts',
                'direction' => 'APP_TO_SHEETS',
                'status' => 'failed',
                'records_processed' => 0,
                'error_message' => $e->getMessage(),
                'started_at' => $startedAt,
                'completed_at' => now(),
            ]);

            throw new RuntimeException("Failed to update contract in Google Sheets: " . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Append a new contract to the spreadsheet.
     *
     * @param array<string, mixed> $data
     * @param int|null $userId
     * @return array<string, mixed>
     */
    public function appendContract(array $data, ?int $userId = null): array
    {
        $startedAt = now();
        $lop = trim((string) ($data['lop'] ?? ''));

        if (empty($lop)) {
            throw new RuntimeException("LOP identifier is required to create a contract.");
        }

        // Check for duplicate LOP
        if ($this->findByLop($lop) !== null) {
            throw new RuntimeException("A contract with LOP '{$lop}' already exists.");
        }

        $headers = $this->getHeaders();
        $rowValues = $this->transformer->toSpreadsheetRow($data, $headers);

        $sheetName = $this->getSheetName();
        $lastColumnLetter = $this->getColumnLetter(count($headers) ?: 21);
        $targetRange = "{$sheetName}!A:{$lastColumnLetter}";

        try {
            $valueRange = new ValueRange();
            $valueRange->setValues([$rowValues]);

            $response = $this->getSheetsService()->spreadsheets_values->append(
                $this->getSpreadsheetId(),
                $targetRange,
                $valueRange,
                [
                    'valueInputOption' => 'USER_ENTERED',
                    'insertDataOption' => 'INSERT_ROWS',
                ]
            );

            // Extract updated row index from response if available
            $updatedRange = $response->getUpdates()?->getUpdatedRange();
            $newRowIndex = null;
            if ($updatedRange && preg_match('/!A(\d+):/', $updatedRange, $matches)) {
                $newRowIndex = (int) $matches[1];
            }

            // Invalidate cache
            $this->clearCache();

            SyncLog::create([
                'user_id' => $userId ?: auth()->id(),
                'sync_type' => 'contracts',
                'direction' => 'APP_TO_SHEETS',
                'status' => 'success',
                'records_processed' => 1,
                'started_at' => $startedAt,
                'completed_at' => now(),
            ]);

            return $this->transformer->transformRow($headers, $rowValues, $newRowIndex);
        } catch (Throwable $e) {
            $this->logError("Failed to append contract LOP '{$lop}' to Google Sheets", $e);

            SyncLog::create([
                'user_id' => $userId ?: auth()->id(),
                'sync_type' => 'contracts',
                'direction' => 'APP_TO_SHEETS',
                'status' => 'failed',
                'records_processed' => 0,
                'error_message' => $e->getMessage(),
                'started_at' => $startedAt,
                'completed_at' => now(),
            ]);

            throw new RuntimeException("Failed to save contract to Google Sheets: " . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Invalidate all contract cache entries.
     */
    public function clearCache(): void
    {
        Cache::forget(self::CACHE_KEY_ALL_CONTRACTS);
        Cache::forget(self::CACHE_KEY_HEADERS);
    }

    /**
     * Force refresh contract data from Google Sheets.
     *
     * @return array<int, array<string, mixed>>
     */
    public function refreshCache(): array
    {
        return $this->getAllContracts(true);
    }

    /**
     * Convert 1-based column number into Excel column letter (e.g. 1 -> A, 14 -> N).
     */
    protected function getColumnLetter(int $columnNumber): string
    {
        $letter = '';
        while ($columnNumber > 0) {
            $remainder = ($columnNumber - 1) % 26;
            $letter = chr(65 + $remainder) . $letter;
            $columnNumber = (int) (($columnNumber - $remainder) / 26);
        }
        return $letter ?: 'N';
    }

    /**
     * Log errors safely without exposing private keys or secrets.
     */
    protected function logError(string $message, Throwable $e): void
    {
        $context = [
            'exception_class' => get_class($e),
            'code' => $e->getCode(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
        ];

        if ($e instanceof \Google\Service\Exception) {
            $context['http_status'] = $e->getCode();
            $context['google_errors'] = $e->getErrors();
            $context['google_message'] = $e->getMessage();
        }

        if ($prev = $e->getPrevious()) {
            $context['previous_class'] = get_class($prev);
            $context['previous_message'] = $prev->getMessage();
            $context['previous_code'] = $prev->getCode();
        }

        Log::error("[GoogleSheetsService] {$message}: " . $e->getMessage(), $context);
    }
}

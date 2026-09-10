<?php

namespace App\Services;

use App\Models\SyncLog;
use Google\Client as GoogleClient;
use Google\Service\Drive as GoogleDrive;
use Google\Service\Drive\DriveFile;
use Illuminate\Support\Facades\Log;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;
use RuntimeException;
use Throwable;

/**
 * GoogleDriveService — PRD FR-12, FR-13, FR-16, Section 10
 *
 * Handles document operations via Google Drive API v3.
 * Capabilities:
 * 1. Validate document reference.
 * 2. Find file.
 * 3. Get file metadata.
 * 4. Verify file availability.
 * 5. Stream media / preview reference.
 */
class GoogleDriveService
{
    protected ?GoogleDrive $driveService = null;

    /**
     * Set a custom Google Drive service (useful for testing with mocks).
     */
    public function setDriveService(GoogleDrive $driveService): self
    {
        $this->driveService = $driveService;
        return $this;
    }

    /**
     * Get or initialize the authenticated Google Drive service.
     */
    public function getDriveService(): GoogleDrive
    {
        if ($this->driveService !== null) {
            return $this->driveService;
        }

        $this->driveService = $this->createAuthenticatedDriveService();
        return $this->driveService;
    }

    /**
     * Create authenticated Google Drive client using Service Account credentials.
     * Principle of Least Privilege: DRIVE_READONLY scope.
     */
    protected function createAuthenticatedDriveService(): GoogleDrive
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
        $client->setScopes([GoogleDrive::DRIVE_READONLY]);
        $client->setAuthConfig($authConfig);

        return new GoogleDrive($client);
    }

    /**
     * Parse and extract Google Drive File ID from various reference formats.
     * Supports:
     * - Pure File ID: "1a2B3c4D5e6F7g..."
     * - URL /file/d/{id}: "https://drive.google.com/file/d/1a2B3c4D5e6F7g/view?usp=sharing"
     * - URL id query param: "https://drive.google.com/open?id=1a2B3c4D5e6F7g"
     * - Docs URL: "https://docs.google.com/document/d/1a2B3c4D5e6F7g/edit"
     */
    public function parseDocumentReference(?string $reference): ?string
    {
        if (empty($reference)) {
            return null;
        }

        $trimmed = trim($reference);

        // Pattern 1: /d/{fileId}
        if (preg_match('#/d/([a-zA-Z0-9_-]{10,})#', $trimmed, $matches)) {
            return $matches[1];
        }

        // Pattern 2: /folders/{folderId}
        if (preg_match('#/folders/([a-zA-Z0-9_-]{10,})#', $trimmed, $matches)) {
            return $matches[1];
        }

        // Pattern 3: id={fileId}
        if (preg_match('#[?&]id=([a-zA-Z0-9_-]{10,})#', $trimmed, $matches)) {
            return $matches[1];
        }

        // Pattern 4: Pure ID (alphanumeric, dashes, underscores, typically >= 10 chars, no slashes or spaces)
        if (preg_match('#^[a-zA-Z0-9_-]{10,}$#', $trimmed)) {
            return $trimmed;
        }

        return null;
    }

    /**
     * Validate if the document reference is well-formed.
     */
    public function validateDocumentReference(?string $reference): bool
    {
        return $this->parseDocumentReference($reference) !== null;
    }

    /**
     * Get file metadata from Google Drive.
     * If the reference is a folder, automatically resolves to the contract file inside.
     *
     * @return array<string, mixed>
     * @throws RuntimeException
     */
    public function getFileMetadata(string $fileId): array
    {
        try {
            $service = $this->getDriveService();

            /** @var DriveFile $file */
            $file = $service->files->get($fileId, [
                'fields' => 'id, name, mimeType, size, webViewLink, webContentLink, iconLink, thumbnailLink',
                'supportsAllDrives' => true,
            ]);

            $mimeType = $file->getMimeType();

            // If reference is a Google Drive folder, resolve to the document inside it
            if ($mimeType === 'application/vnd.google-apps.folder') {
                $children = $service->files->listFiles([
                    'q' => "'{$fileId}' in parents and trashed = false",
                    'fields' => 'files(id, name, mimeType, size, webViewLink, webContentLink, iconLink, thumbnailLink)',
                    'supportsAllDrives' => true,
                    'includeItemsFromAllDrives' => true,
                    'orderBy' => 'createdTime desc',
                    'pageSize' => 10,
                ]);

                $files = $children->getFiles();
                $targetFile = null;
                $allowedMimes = config('google.drive.allowed_mime_types', ['application/pdf', 'image/png', 'image/jpeg']);

                foreach ($files as $child) {
                    if (in_array($child->getMimeType(), $allowedMimes)) {
                        $targetFile = $child;
                        break;
                    }
                }
                if (!$targetFile && !empty($files)) {
                    $targetFile = $files[0];
                }

                if ($targetFile) {
                    return [
                        'id' => $targetFile->getId(),
                        'parent_folder_id' => $fileId,
                        'name' => $targetFile->getName(),
                        'mime_type' => $targetFile->getMimeType(),
                        'size' => (int) $targetFile->getSize(),
                        'size_formatted' => $this->formatFileSize((int) $targetFile->getSize()),
                        'web_view_link' => $targetFile->getWebViewLink(),
                        'web_content_link' => $targetFile->getWebContentLink(),
                        'icon_link' => $targetFile->getIconLink(),
                        'thumbnail_link' => $targetFile->getThumbnailLink(),
                        'is_pdf' => $targetFile->getMimeType() === 'application/pdf',
                        'is_image' => str_starts_with((string) $targetFile->getMimeType(), 'image/'),
                        'is_previewable' => in_array($targetFile->getMimeType(), $allowedMimes),
                        'preview_url' => $this->getPreviewUrl($targetFile->getId()),
                    ];
                }

                throw new RuntimeException("Folder Google Drive '{$file->getName()}' tidak berisi file dokumen (PDF/Gambar).");
            }

            return [
                'id' => $file->getId(),
                'name' => $file->getName(),
                'mime_type' => $file->getMimeType(),
                'size' => (int) $file->getSize(),
                'size_formatted' => $this->formatFileSize((int) $file->getSize()),
                'web_view_link' => $file->getWebViewLink(),
                'web_content_link' => $file->getWebContentLink(),
                'icon_link' => $file->getIconLink(),
                'thumbnail_link' => $file->getThumbnailLink(),
                'is_pdf' => $file->getMimeType() === 'application/pdf',
                'is_image' => str_starts_with((string) $file->getMimeType(), 'image/'),
                'is_previewable' => in_array($file->getMimeType(), config('google.drive.allowed_mime_types', ['application/pdf', 'image/png', 'image/jpeg'])),
                'preview_url' => $this->getPreviewUrl($file->getId()),
            ];
        } catch (Throwable $e) {
            Log::error("Google Drive API error getting metadata for file '{$fileId}': " . $e->getMessage(), [
                'file_id' => $fileId,
                'code' => $e->getCode(),
            ]);

            try {
                SyncLog::record(
                    syncType: 'DRIVE_GET_METADATA',
                    direction: 'SHEETS_TO_APP',
                    status: 'FAILED',
                    recordsProcessed: 0,
                    errorMessage: $e->getMessage(),
                    startedAt: now(),
                    completedAt: now()
                );
            } catch (Throwable) {
                // Ignore sync log failure in error handler
            }

            throw new RuntimeException("Gagal mengambil metadata file Google Drive: " . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Verify file availability on Google Drive and return structured status.
     *
     * @return array{
     *     available: bool,
     *     status: 'available'|'not_found'|'access_denied'|'invalid_document'|'error',
     *     metadata: array<string, mixed>|null,
     *     error: string|null
     * }
     */
    public function verifyFileAvailability(?string $reference): array
    {
        $fileId = $this->parseDocumentReference($reference);

        if (!$fileId) {
            return [
                'available' => false,
                'status' => 'invalid_document',
                'metadata' => null,
                'error' => 'Format referensi dokumen tidak valid.',
            ];
        }

        try {
            $metadata = $this->getFileMetadata($fileId);

            return [
                'available' => true,
                'status' => 'available',
                'metadata' => $metadata,
                'error' => null,
            ];
        } catch (Throwable $e) {
            $code = $e->getCode();
            $message = $e->getMessage();

            if ($code === 404 || str_contains($message, 'File not found') || str_contains($message, '404')) {
                return [
                    'available' => false,
                    'status' => 'not_found',
                    'metadata' => null,
                    'error' => 'File tidak ditemukan di Google Drive.',
                ];
            }

            if ($code === 403 || str_contains($message, 'Access denied') || str_contains($message, 'Permission denied') || str_contains($message, '403')) {
                return [
                    'available' => false,
                    'status' => 'access_denied',
                    'metadata' => null,
                    'error' => 'Akses ditolak: Service Account tidak memiliki izin membaca file ini.',
                ];
            }

            return [
                'available' => false,
                'status' => 'error',
                'metadata' => null,
                'error' => 'Terjadi kesalahan saat memverifikasi file di Google Drive: ' . $message,
            ];
        }
    }

    /**
     * Download and stream file content from Google Drive for server-side proxying.
     *
     * @param string $fileId
     * @return array{
     *     stream: StreamInterface|string,
     *     mime_type: string,
     *     name: string,
     *     size: int
     * }
     * @throws RuntimeException
     */
    public function getFileStream(string $fileId): array
    {
        $metadata = $this->getFileMetadata($fileId);

        // Security check: only allow previewable MIME types
        $allowedMimeTypes = config('google.drive.allowed_mime_types', ['application/pdf', 'image/png', 'image/jpeg']);
        if (!in_array($metadata['mime_type'], $allowedMimeTypes)) {
            throw new RuntimeException("Tipe file '{$metadata['mime_type']}' tidak diizinkan untuk di-preview melalui aplikasi.");
        }

        // Security check: size limit
        $maxBytes = ((int) config('google.drive.max_preview_size_mb', 25)) * 1024 * 1024;
        if ($metadata['size'] > $maxBytes) {
            throw new RuntimeException("Ukuran file melebihi batas preview maksimum (" . config('google.drive.max_preview_size_mb', 25) . " MB).");
        }

        try {
            $service = $this->getDriveService();
            $actualFileId = $metadata['id'] ?? $fileId;

            /** @var ResponseInterface $response */
            $response = $service->files->get($actualFileId, [
                'alt' => 'media',
                'supportsAllDrives' => true,
            ]);

            return [
                'stream' => $response->getBody(),
                'mime_type' => $metadata['mime_type'],
                'name' => $metadata['name'],
                'size' => $metadata['size'],
            ];
        } catch (Throwable $e) {
            Log::error("Google Drive API error streaming file '{$fileId}': " . $e->getMessage(), [
                'file_id' => $fileId,
            ]);

            try {
                SyncLog::record(
                    syncType: 'DRIVE_GET_STREAM',
                    direction: 'SHEETS_TO_APP',
                    status: 'FAILED',
                    recordsProcessed: 0,
                    errorMessage: $e->getMessage(),
                    startedAt: now(),
                    completedAt: now()
                );
            } catch (Throwable) {
                // Ignore sync log failure in error handler
            }

            throw new RuntimeException("Gagal mengunduh konten file dari Google Drive: " . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Generate standard Google Drive embedded preview URL.
     */
    public function getPreviewUrl(string $fileId): string
    {
        return "https://drive.google.com/file/d/{$fileId}/preview";
    }

    /**
     * Helper to format bytes into readable size.
     */
    protected function formatFileSize(int $bytes): string
    {
        if ($bytes <= 0) {
            return '0 B';
        }

        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $i = (int) floor(log($bytes, 1024));
        $i = min($i, count($units) - 1);

        return round($bytes / pow(1024, $i), 2) . ' ' . $units[$i];
    }
}

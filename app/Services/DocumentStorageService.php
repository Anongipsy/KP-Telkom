<?php

namespace App\Services;

use App\Models\ContractDocument;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use InvalidArgumentException;
use RuntimeException;

class DocumentStorageService
{
    /**
     * Get configured storage disk.
     */
    public function getDisk(): string
    {
        return config('documents.disk', 'local');
    }

    /**
     * Get configured relative storage directory path.
     */
    public function getBasePath(): string
    {
        return config('documents.path', 'documents');
    }

    /**
     * Get maximum allowed file size in bytes (10MB default).
     */
    public function getMaxSizeBytes(): int
    {
        return config('documents.max_upload_size_kb', 10240) * 1024;
    }

    /**
     * Validate an uploaded file against allowed MIME types and size limit.
     *
     * @throws InvalidArgumentException
     */
    public function validateFile(UploadedFile $file): void
    {
        if (!$file->isValid()) {
            throw new InvalidArgumentException('File yang di-upload tidak valid atau rusak: ' . $file->getErrorMessage());
        }

        $allowedMimes = config('documents.allowed_mime_types', [
            'application/pdf',
            'image/png',
            'image/jpeg',
            'image/jpg',
            'image/webp',
        ]);

        $mimeType = $file->getMimeType() ?? '';
        $extension = strtolower($file->getClientOriginalExtension());

        $allowedExtensions = config('documents.allowed_extensions', [
            'pdf',
            'png',
            'jpg',
            'jpeg',
            'webp',
        ]);

        if (!in_array($extension, $allowedExtensions, true) && !in_array($mimeType, $allowedMimes, true)) {
            throw new InvalidArgumentException("Format file tidak didukung (.{$extension}). Hanya file PDF dan gambar (PNG, JPG, WebP) yang diperbolehkan.");
        }

        $maxSize = $this->getMaxSizeBytes();
        if ($file->getSize() > $maxSize) {
            $maxMb = config('documents.max_upload_size_mb', 10);
            throw new InvalidArgumentException("Ukuran file melebihi batas maksimum {$maxMb}MB.");
        }
    }

    /**
     * Upload and store a contract document.
     * Replaces existing document if one already exists for the given LOP.
     *
     * @throws InvalidArgumentException|RuntimeException
     */
    public function upload(UploadedFile $file, string $lop, ?int $userId = null): ContractDocument
    {
        $this->validateFile($file);

        $disk = $this->getDisk();
        $baseDir = $this->getBasePath();

        // Remove old document if exists
        $existing = ContractDocument::where('lop', $lop)->first();
        if ($existing) {
            $this->deleteDocument($lop);
        }

        $extension = strtolower($file->getClientOriginalExtension()) ?: 'bin';
        $storedName = Str::uuid()->toString() . '.' . $extension;
        $relativeStoragePath = trim($baseDir, '/') . '/' . $storedName;

        $storedPath = Storage::disk($disk)->putFileAs(
            $baseDir,
            $file,
            $storedName
        );

        if (!$storedPath) {
            Log::error("Failed to store contract document to disk '{$disk}' for LOP '{$lop}'");
            throw new RuntimeException('Gagal menyimpan file dokumen ke disk server.');
        }

        return ContractDocument::create([
            'lop' => $lop,
            'original_name' => $file->getClientOriginalName(),
            'stored_name' => $storedName,
            'mime_type' => $file->getMimeType() ?? 'application/octet-stream',
            'size' => $file->getSize(),
            'disk' => $disk,
            'path' => $storedPath,
            'uploaded_by' => $userId,
        ]);
    }

    /**
     * Get document metadata by LOP.
     */
    public function getDocument(string $lop): ?ContractDocument
    {
        return ContractDocument::where('lop', $lop)->first();
    }

    /**
     * Check if a document exists for the given LOP.
     */
    public function hasDocument(string $lop): bool
    {
        return ContractDocument::where('lop', $lop)->exists();
    }

    /**
     * Get all LOPs that currently have stored documents.
     *
     * @return array<string, bool> Map of lop => true
     */
    public function getAllDocumentLopsMap(): array
    {
        return ContractDocument::pluck('lop')->flip()->map(fn () => true)->toArray();
    }

    /**
     * Verify document availability for metadata endpoint / modal preview.
     *
     * @return array{available: bool, status: string, metadata: ?array, error: ?string}
     */
    public function verifyFileAvailability(string $lop): array
    {
        $doc = $this->getDocument($lop);

        if (!$doc) {
            return [
                'available' => false,
                'status' => 'not_found',
                'metadata' => null,
                'error' => "Belum ada dokumen yang di-upload untuk kontrak '{$lop}'.",
            ];
        }

        if (!Storage::disk($doc->disk)->exists($doc->path)) {
            Log::warning("Physical document missing on disk: {$doc->path} for LOP: {$lop}");
            return [
                'available' => false,
                'status' => 'not_found',
                'metadata' => null,
                'error' => 'File dokumen fisik tidak ditemukan di server penyimpanan.',
            ];
        }

        return [
            'available' => true,
            'status' => 'available',
            'metadata' => $doc->toMetadataArray(),
            'error' => null,
        ];
    }

    /**
     * Get file stream resource and info for proxy streaming.
     *
     * @return array{name: string, mime_type: string, size: int, stream: resource, path: string, disk: string}
     * @throws RuntimeException
     */
    public function getFileStream(string $lop): array
    {
        $doc = $this->getDocument($lop);

        if (!$doc) {
            throw new RuntimeException("Dokumen untuk kontrak '{$lop}' tidak ditemukan.");
        }

        if (!Storage::disk($doc->disk)->exists($doc->path)) {
            throw new RuntimeException("File fisik dokumen untuk LOP '{$lop}' tidak ditemukan di server.");
        }

        $stream = Storage::disk($doc->disk)->readStream($doc->path);

        if ($stream === false || $stream === null) {
            throw new RuntimeException("Gagal membuka stream file untuk dokumen '{$doc->original_name}'.");
        }

        return [
            'name' => $doc->original_name,
            'mime_type' => $doc->mime_type,
            'size' => $doc->size,
            'stream' => $stream,
            'path' => $doc->path,
            'disk' => $doc->disk,
        ];
    }

    /**
     * Delete document from disk and database by LOP.
     */
    public function deleteDocument(string $lop): bool
    {
        $doc = $this->getDocument($lop);

        if (!$doc) {
            return false;
        }

        try {
            if (Storage::disk($doc->disk)->exists($doc->path)) {
                Storage::disk($doc->disk)->delete($doc->path);
            }
        } catch (\Throwable $e) {
            Log::warning("Failed to delete physical file from disk: {$doc->path}. Error: " . $e->getMessage());
        }

        return (bool) $doc->delete();
    }
}

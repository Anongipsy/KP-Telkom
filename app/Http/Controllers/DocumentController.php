<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Services\ContractService;
use App\Services\DocumentStorageService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

/**
 * DocumentController — PRD FR-12, FR-13, FR-16
 *
 * Secure server-side controller for local contract document metadata,
 * streaming preview proxy, file upload, and document deletion.
 */
class DocumentController extends Controller
{
    public function __construct(
        protected ContractService $contractService,
        protected DocumentStorageService $documentStorage
    ) {}

    /**
     * Get document metadata and availability for a contract.
     */
    public function metadata(Request $request, string $lop): JsonResponse
    {
        try {
            $contract = $this->contractService->findByLop($lop);

            if (!$contract) {
                return response()->json([
                    'success' => false,
                    'status' => 'not_found',
                    'error' => "Kontrak dengan LOP '{$lop}' tidak ditemukan.",
                ], 404);
            }

            $result = $this->documentStorage->verifyFileAvailability($lop);

            if ($result['available'] && $result['metadata']) {
                // Attach proxy URL for secure inline preview
                $result['metadata']['proxy_url'] = route('contracts.document.proxy', $lop);

                // Audit Log
                AuditLog::record(
                    action: 'document_view_metadata',
                    userId: $request->user()?->id,
                    targetType: 'contract_document',
                    targetReference: $lop,
                    metadata: [
                        'file_id' => $result['metadata']['id'],
                        'file_name' => $result['metadata']['name'],
                        'mime_type' => $result['metadata']['mime_type'],
                    ]
                );
            }

            return response()->json([
                'success' => $result['available'],
                'status' => $result['status'],
                'lop' => $lop,
                'metadata' => $result['metadata'],
                'error' => $result['error'],
            ], $result['available'] ? 200 : ($result['status'] === 'not_found' ? 404 : 400));
        } catch (Throwable $e) {
            Log::error("DocumentController@metadata error for LOP '{$lop}': " . $e->getMessage(), [
                'lop' => $lop,
                'user_id' => $request->user()?->id,
            ]);

            return response()->json([
                'success' => false,
                'status' => 'error',
                'error' => 'Gagal memeriksa dokumen kontrak. Silakan coba beberapa saat lagi.',
            ], 500);
        }
    }

    /**
     * Stream file content through server proxy.
     * Prevents direct unprotected access to storage paths.
     */
    public function proxy(Request $request, string $lop): StreamedResponse|\Illuminate\Http\Response
    {
        try {
            $contract = $this->contractService->findByLop($lop);

            if (!$contract) {
                abort(404, "Kontrak dengan LOP '{$lop}' tidak ditemukan.");
            }

            $doc = $this->documentStorage->getDocument($lop);

            if (!$doc) {
                abort(404, "Kontrak '{$lop}' belum memiliki dokumen.");
            }

            $fileData = $this->documentStorage->getFileStream($lop);

            // Audit Log
            AuditLog::record(
                action: 'document_view_stream',
                userId: $request->user()?->id,
                targetType: 'contract_document',
                targetReference: $lop,
                metadata: [
                    'file_name' => $fileData['name'],
                    'mime_type' => $fileData['mime_type'],
                    'size' => $fileData['size'],
                ]
            );

            return response()->stream(function () use ($fileData) {
                $stream = $fileData['stream'];
                if (is_resource($stream)) {
                    fpassthru($stream);
                    fclose($stream);
                } elseif (is_string($stream)) {
                    echo $stream;
                }
            }, 200, [
                'Content-Type' => $fileData['mime_type'],
                'Content-Disposition' => 'inline; filename="' . addslashes($fileData['name']) . '"',
                'Cache-Control' => 'private, max-age=3600',
                'X-Content-Type-Options' => 'nosniff',
            ]);
        } catch (\Symfony\Component\HttpKernel\Exception\HttpExceptionInterface $e) {
            throw $e;
        } catch (Throwable $e) {
            Log::error("DocumentController@proxy error for LOP '{$lop}': " . $e->getMessage(), [
                'lop' => $lop,
                'user_id' => $request->user()?->id,
            ]);

            abort(500, 'Gagal memuat dokumen kontrak dari server penyimpanan.');
        }
    }

    /**
     * Upload a new contract document directly.
     */
    public function upload(Request $request, string $lop): RedirectResponse
    {
        $allowedExtensions = implode(',', config('documents.allowed_extensions', ['pdf', 'png', 'jpg', 'jpeg', 'webp']));
        $maxKb = config('documents.max_upload_size_kb', 10240);
        $maxMb = config('documents.max_upload_size_mb', 10);

        $request->validate([
            'document_file' => [
                'required',
                'file',
                "mimes:{$allowedExtensions}",
                "max:{$maxKb}",
            ],
        ], [
            'document_file.required' => 'Pilih file dokumen terlebih dahulu.',
            'document_file.file' => 'File yang diunggah tidak valid.',
            'document_file.mimes' => 'Format file harus berupa PDF atau gambar (PNG, JPG, WebP).',
            'document_file.max' => "Ukuran file dokumen tidak boleh melebihi {$maxMb}MB.",
        ]);

        try {
            $contract = $this->contractService->findByLop($lop);

            if (!$contract) {
                return back()->with('error', "Kontrak dengan LOP '{$lop}' tidak ditemukan.");
            }

            $doc = $this->documentStorage->upload(
                file: $request->file('document_file'),
                lop: $lop,
                userId: $request->user()?->id
            );

            // Audit Log
            AuditLog::record(
                action: 'document_upload',
                userId: $request->user()?->id,
                targetType: 'contract_document',
                targetReference: $lop,
                metadata: [
                    'file_name' => $doc->original_name,
                    'mime_type' => $doc->mime_type,
                    'size' => $doc->size,
                ]
            );

            return back()->with('status', "Dokumen '{$doc->original_name}' berhasil di-upload ke server.");
        } catch (Throwable $e) {
            Log::error("Failed to upload document for LOP '{$lop}': " . $e->getMessage(), [
                'lop' => $lop,
                'user_id' => $request->user()?->id,
            ]);

            return back()->with('error', 'Gagal mengunggah dokumen: ' . $e->getMessage());
        }
    }

    /**
     * Delete an existing contract document.
     */
    public function destroy(Request $request, string $lop): RedirectResponse
    {
        try {
            $contract = $this->contractService->findByLop($lop);

            if (!$contract) {
                return back()->with('error', "Kontrak dengan LOP '{$lop}' tidak ditemukan.");
            }

            $doc = $this->documentStorage->getDocument($lop);

            if (!$doc) {
                return back()->with('error', 'Tidak ada dokumen yang tersimpan untuk kontrak ini.');
            }

            $fileName = $doc->original_name;
            $deleted = $this->documentStorage->deleteDocument($lop);

            if (!$deleted) {
                return back()->with('error', 'Gagal menghapus dokumen dari server.');
            }

            // Audit Log
            AuditLog::record(
                action: 'document_delete',
                userId: $request->user()?->id,
                targetType: 'contract_document',
                targetReference: $lop,
                metadata: [
                    'file_name' => $fileName,
                ]
            );

            return back()->with('status', "Dokumen '{$fileName}' berhasil dihapus dari server.");
        } catch (Throwable $e) {
            Log::error("Failed to delete document for LOP '{$lop}': " . $e->getMessage(), [
                'lop' => $lop,
                'user_id' => $request->user()?->id,
            ]);

            return back()->with('error', 'Gagal menghapus dokumen: ' . $e->getMessage());
        }
    }
}

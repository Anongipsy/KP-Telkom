<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Services\ContractService;
use App\Services\GoogleDriveService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

/**
 * DocumentController — PRD FR-12, FR-13, FR-16
 *
 * Secure server-side controller for Google Drive document metadata and streaming preview proxy.
 */
class DocumentController extends Controller
{
    public function __construct(
        protected ContractService $contractService,
        protected GoogleDriveService $driveService
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

            $docRef = $contract['document_reference'] ?? null;

            if (empty($docRef)) {
                return response()->json([
                    'success' => false,
                    'status' => 'invalid_document',
                    'error' => 'Kontrak ini belum memiliki referensi dokumen Google Drive.',
                ], 400);
            }

            $result = $this->driveService->verifyFileAvailability($docRef);

            if ($result['available'] && $result['metadata']) {
                $fileId = $result['metadata']['id'];

                // Attach proxy URL for secure inline preview
                $result['metadata']['proxy_url'] = route('contracts.document.proxy', $lop);

                // Audit Log
                AuditLog::record(
                    action: 'document_view_metadata',
                    userId: $request->user()->id,
                    targetType: 'contract_document',
                    targetReference: $lop,
                    metadata: [
                        'file_id' => $fileId,
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
            ]);
        } catch (Throwable $e) {
            Log::error("DocumentController@metadata error for LOP '{$lop}': " . $e->getMessage(), [
                'lop' => $lop,
                'user_id' => $request->user()->id,
            ]);

            return response()->json([
                'success' => false,
                'status' => 'error',
                'error' => 'Gagal memeriksa dokumen dari Google Drive. Silakan coba beberapa saat lagi.',
            ], 500);
        }
    }

    /**
     * Stream file content from Google Drive through server proxy.
     * Prevents exposing Service Account credentials or direct unrestricted Drive access.
     */
    public function proxy(Request $request, string $lop): StreamedResponse|\Illuminate\Http\Response
    {
        try {
            $contract = $this->contractService->findByLop($lop);

            if (!$contract) {
                abort(404, "Kontrak dengan LOP '{$lop}' tidak ditemukan.");
            }

            $docRef = $contract['document_reference'] ?? null;

            if (empty($docRef)) {
                abort(404, 'Kontrak tidak memiliki referensi dokumen.');
            }

            $fileId = $this->driveService->parseDocumentReference($docRef);

            if (!$fileId) {
                abort(400, 'Format referensi dokumen tidak valid.');
            }

            $fileData = $this->driveService->getFileStream($fileId);

            // Audit Log
            AuditLog::record(
                action: 'document_view_stream',
                userId: $request->user()->id,
                targetType: 'contract_document',
                targetReference: $lop,
                metadata: [
                    'file_id' => $fileId,
                    'file_name' => $fileData['name'],
                    'mime_type' => $fileData['mime_type'],
                    'size' => $fileData['size'],
                ]
            );

            return response()->stream(function () use ($fileData) {
                $stream = $fileData['stream'];
                if (is_string($stream)) {
                    echo $stream;
                } else {
                    while (!$stream->eof()) {
                        echo $stream->read(8192);
                    }
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
                'user_id' => $request->user()->id,
            ]);

            abort(500, 'Gagal memuat dokumen dari Google Drive. Silakan coba beberapa saat lagi.');
        }
    }
}

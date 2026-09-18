<?php

namespace Tests\Feature;

use App\Models\ContractDocument;
use App\Models\User;
use App\Services\DocumentStorageService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use InvalidArgumentException;
use Tests\TestCase;

class DocumentStorageServiceTest extends TestCase
{
    use RefreshDatabase;

    protected DocumentStorageService $service;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        $this->service = app(DocumentStorageService::class);
    }

    public function test_can_upload_pdf_document_successfully(): void
    {
        $user = User::factory()->create();
        $file = UploadedFile::fake()->create('kontrak_pt_telkom.pdf', 1024, 'application/pdf');

        $doc = $this->service->upload($file, 'LOP-2026-TEST', $user->id);

        $this->assertInstanceOf(ContractDocument::class, $doc);
        $this->assertEquals('LOP-2026-TEST', $doc->lop);
        $this->assertEquals('kontrak_pt_telkom.pdf', $doc->original_name);
        $this->assertEquals('application/pdf', $doc->mime_type);
        $this->assertTrue($doc->isPdf());
        $this->assertTrue($doc->isPreviewable());

        $this->assertDatabaseHas('contract_documents', [
            'lop' => 'LOP-2026-TEST',
            'original_name' => 'kontrak_pt_telkom.pdf',
        ]);

        Storage::disk('local')->assertExists($doc->path);
    }

    public function test_can_upload_image_document(): void
    {
        $file = UploadedFile::fake()->create('bukti_kontrak.png', 500, 'image/png');

        $doc = $this->service->upload($file, 'LOP-IMG-001');

        $this->assertTrue($doc->isImage());
        $this->assertTrue($doc->isPreviewable());
        $this->assertFalse($doc->isPdf());
        Storage::disk('local')->assertExists($doc->path);
    }

    public function test_uploading_new_file_replaces_existing_document_for_same_lop(): void
    {
        $file1 = UploadedFile::fake()->create('old_contract.pdf', 500, 'application/pdf');
        $doc1 = $this->service->upload($file1, 'LOP-DUP-01');
        $oldPath = $doc1->path;

        Storage::disk('local')->assertExists($oldPath);

        $file2 = UploadedFile::fake()->create('new_contract.pdf', 600, 'application/pdf');
        $doc2 = $this->service->upload($file2, 'LOP-DUP-01');

        // Only one record should exist for this LOP
        $this->assertEquals(1, ContractDocument::where('lop', 'LOP-DUP-01')->count());
        $this->assertEquals('new_contract.pdf', $doc2->original_name);

        // Old file must be deleted from storage
        Storage::disk('local')->assertMissing($oldPath);
        Storage::disk('local')->assertExists($doc2->path);
    }

    public function test_rejects_unsupported_file_extension(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $file = UploadedFile::fake()->create('dangerous_script.exe', 500, 'application/x-msdownload');
        $this->service->upload($file, 'LOP-ERR-01');
    }

    public function test_rejects_file_exceeding_max_size_limit(): void
    {
        $this->expectException(InvalidArgumentException::class);

        // 11MB file exceeds 10MB limit
        $file = UploadedFile::fake()->create('large_archive.pdf', 11 * 1024, 'application/pdf');
        $this->service->upload($file, 'LOP-ERR-02');
    }

    public function test_get_document_and_has_document(): void
    {
        $file = UploadedFile::fake()->create('doc.pdf', 200, 'application/pdf');
        $this->service->upload($file, 'LOP-CHECK-01');

        $this->assertTrue($this->service->hasDocument('LOP-CHECK-01'));
        $this->assertFalse($this->service->hasDocument('LOP-NONEXISTENT'));

        $doc = $this->service->getDocument('LOP-CHECK-01');
        $this->assertNotNull($doc);
        $this->assertEquals('doc.pdf', $doc->original_name);
    }

    public function test_get_all_document_lops_map(): void
    {
        $f1 = UploadedFile::fake()->create('doc1.pdf', 200, 'application/pdf');
        $f2 = UploadedFile::fake()->create('doc2.pdf', 200, 'application/pdf');

        $this->service->upload($f1, 'LOP-A');
        $this->service->upload($f2, 'LOP-B');

        $map = $this->service->getAllDocumentLopsMap();

        $this->assertArrayHasKey('LOP-A', $map);
        $this->assertArrayHasKey('LOP-B', $map);
        $this->assertArrayNotHasKey('LOP-C', $map);
    }

    public function test_verify_file_availability_returns_expected_structure(): void
    {
        // 1. Not found
        $result = $this->service->verifyFileAvailability('LOP-NOT-EXIST');
        $this->assertFalse($result['available']);
        $this->assertEquals('not_found', $result['status']);
        $this->assertNull($result['metadata']);

        // 2. Available
        $file = UploadedFile::fake()->create('available.pdf', 300, 'application/pdf');
        $this->service->upload($file, 'LOP-AVAIL');

        $result2 = $this->service->verifyFileAvailability('LOP-AVAIL');
        $this->assertTrue($result2['available']);
        $this->assertEquals('available', $result2['status']);
        $this->assertNotNull($result2['metadata']);
        $this->assertEquals('available.pdf', $result2['metadata']['name']);
        $this->assertTrue($result2['metadata']['is_pdf']);
    }

    public function test_get_file_stream_returns_valid_resource(): void
    {
        $file = UploadedFile::fake()->create('streamable.pdf', 100, 'application/pdf');
        $this->service->upload($file, 'LOP-STREAM');

        $streamData = $this->service->getFileStream('LOP-STREAM');

        $this->assertIsArray($streamData);
        $this->assertEquals('streamable.pdf', $streamData['name']);
        $this->assertEquals('application/pdf', $streamData['mime_type']);
        $this->assertIsResource($streamData['stream']);

        fclose($streamData['stream']);
    }

    public function test_delete_document_removes_from_disk_and_database(): void
    {
        $file = UploadedFile::fake()->create('to_delete.pdf', 150, 'application/pdf');
        $doc = $this->service->upload($file, 'LOP-DEL');
        $path = $doc->path;

        Storage::disk('local')->assertExists($path);
        $this->assertDatabaseHas('contract_documents', ['lop' => 'LOP-DEL']);

        $result = $this->service->deleteDocument('LOP-DEL');
        $this->assertTrue($result);

        Storage::disk('local')->assertMissing($path);
        $this->assertDatabaseMissing('contract_documents', ['lop' => 'LOP-DEL']);

        // Deleting nonexistent returns false
        $this->assertFalse($this->service->deleteDocument('LOP-DEL'));
    }
}

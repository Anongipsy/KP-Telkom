<?php

namespace Tests\Feature;

use App\Services\GoogleDriveService;
use Google\Service\Drive as GoogleDrive;
use Google\Service\Drive\DriveFile;
use Google\Service\Drive\Resource\Files as DriveFilesResource;
use GuzzleHttp\Psr7\Utils;
use Mockery;
use RuntimeException;
use Tests\TestCase;

class GoogleDriveServiceTest extends TestCase
{
    protected GoogleDriveService $driveService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->driveService = new GoogleDriveService();
    }

    public function test_parse_document_reference_from_pure_id(): void
    {
        $id = '1a2B3c4D5e6F7g8H9i0J';
        $this->assertEquals($id, $this->driveService->parseDocumentReference($id));
    }

    public function test_parse_document_reference_from_drive_file_url(): void
    {
        $url = 'https://drive.google.com/file/d/1a2B3c4D5e6F7g8H9i0J/view?usp=sharing';
        $this->assertEquals('1a2B3c4D5e6F7g8H9i0J', $this->driveService->parseDocumentReference($url));
    }

    public function test_parse_document_reference_from_open_id_url(): void
    {
        $url = 'https://drive.google.com/open?id=1a2B3c4D5e6F7g8H9i0J';
        $this->assertEquals('1a2B3c4D5e6F7g8H9i0J', $this->driveService->parseDocumentReference($url));
    }

    public function test_parse_document_reference_from_docs_url(): void
    {
        $url = 'https://docs.google.com/document/d/1a2B3c4D5e6F7g8H9i0J/edit';
        $this->assertEquals('1a2B3c4D5e6F7g8H9i0J', $this->driveService->parseDocumentReference($url));
    }

    public function test_parse_invalid_reference_returns_null(): void
    {
        $this->assertNull($this->driveService->parseDocumentReference(''));
        $this->assertNull($this->driveService->parseDocumentReference(null));
        $this->assertNull($this->driveService->parseDocumentReference('short'));
        $this->assertNull($this->driveService->parseDocumentReference('   '));
    }

    public function test_validate_document_reference(): void
    {
        $this->assertTrue($this->driveService->validateDocumentReference('1a2B3c4D5e6F7g8H9i0J'));
        $this->assertTrue($this->driveService->validateDocumentReference('https://drive.google.com/file/d/1a2B3c4D5e6F7g8H9i0J/view'));
        $this->assertFalse($this->driveService->validateDocumentReference(''));
        $this->assertFalse($this->driveService->validateDocumentReference(null));
    }

    public function test_get_preview_url(): void
    {
        $fileId = '1a2B3c4D5e6F7g8H9i0J';
        $this->assertEquals("https://drive.google.com/file/d/{$fileId}/preview", $this->driveService->getPreviewUrl($fileId));
    }

    public function test_get_file_metadata_success(): void
    {
        $fileId = '1a2B3c4D5e6F7g8H9i0J';

        $mockDriveFile = new DriveFile([
            'id' => $fileId,
            'name' => 'Kontrak_Telkom_2026.pdf',
            'mimeType' => 'application/pdf',
            'size' => 1048576, // 1 MB
            'webViewLink' => "https://drive.google.com/file/d/{$fileId}/view",
            'webContentLink' => "https://drive.google.com/uc?id={$fileId}",
            'iconLink' => 'https://drive.google.com/icon.png',
            'thumbnailLink' => 'https://drive.google.com/thumb.png',
        ]);

        $mockFilesResource = Mockery::mock(DriveFilesResource::class);
        $mockFilesResource->shouldReceive('get')
            ->once()
            ->with($fileId, Mockery::type('array'))
            ->andReturn($mockDriveFile);

        $mockDrive = Mockery::mock(GoogleDrive::class);
        $mockDrive->files = $mockFilesResource;

        $this->driveService->setDriveService($mockDrive);

        $metadata = $this->driveService->getFileMetadata($fileId);

        $this->assertEquals($fileId, $metadata['id']);
        $this->assertEquals('Kontrak_Telkom_2026.pdf', $metadata['name']);
        $this->assertEquals('application/pdf', $metadata['mime_type']);
        $this->assertTrue($metadata['is_pdf']);
        $this->assertTrue($metadata['is_previewable']);
        $this->assertEquals('1 MB', $metadata['size_formatted']);
    }

    public function test_verify_file_availability_success(): void
    {
        $fileId = '1a2B3c4D5e6F7g8H9i0J';

        $mockDriveFile = new DriveFile([
            'id' => $fileId,
            'name' => 'Dokumen_SPPO.pdf',
            'mimeType' => 'application/pdf',
            'size' => 512000,
        ]);

        $mockFilesResource = Mockery::mock(DriveFilesResource::class);
        $mockFilesResource->shouldReceive('get')
            ->once()
            ->with($fileId, Mockery::type('array'))
            ->andReturn($mockDriveFile);

        $mockDrive = Mockery::mock(GoogleDrive::class);
        $mockDrive->files = $mockFilesResource;

        $this->driveService->setDriveService($mockDrive);

        $result = $this->driveService->verifyFileAvailability($fileId);

        $this->assertTrue($result['available']);
        $this->assertEquals('available', $result['status']);
        $this->assertNotNull($result['metadata']);
        $this->assertNull($result['error']);
    }

    public function test_verify_file_availability_not_found(): void
    {
        $fileId = '1a2B3c4D5e6F7g8H9i0J';

        $mockFilesResource = Mockery::mock(DriveFilesResource::class);
        $mockFilesResource->shouldReceive('get')
            ->once()
            ->with($fileId, Mockery::type('array'))
            ->andThrow(new \Google\Service\Exception('File not found', 404));

        $mockDrive = Mockery::mock(GoogleDrive::class);
        $mockDrive->files = $mockFilesResource;

        $this->driveService->setDriveService($mockDrive);

        $result = $this->driveService->verifyFileAvailability($fileId);

        $this->assertFalse($result['available']);
        $this->assertEquals('not_found', $result['status']);
        $this->assertNull($result['metadata']);
        $this->assertStringContainsString('tidak ditemukan', $result['error']);
    }

    public function test_verify_file_availability_access_denied(): void
    {
        $fileId = '1a2B3c4D5e6F7g8H9i0J';

        $mockFilesResource = Mockery::mock(DriveFilesResource::class);
        $mockFilesResource->shouldReceive('get')
            ->once()
            ->with($fileId, Mockery::type('array'))
            ->andThrow(new \Google\Service\Exception('Access denied', 403));

        $mockDrive = Mockery::mock(GoogleDrive::class);
        $mockDrive->files = $mockFilesResource;

        $this->driveService->setDriveService($mockDrive);

        $result = $this->driveService->verifyFileAvailability($fileId);

        $this->assertFalse($result['available']);
        $this->assertEquals('access_denied', $result['status']);
        $this->assertNull($result['metadata']);
        $this->assertStringContainsString('Akses ditolak', $result['error']);
    }

    public function test_verify_file_availability_invalid_reference(): void
    {
        $result = $this->driveService->verifyFileAvailability('');

        $this->assertFalse($result['available']);
        $this->assertEquals('invalid_document', $result['status']);
        $this->assertNull($result['metadata']);
    }

    public function test_get_file_stream_disallowed_mime_type_throws_exception(): void
    {
        $fileId = '1a2B3c4D5e6F7g8H9i0J';

        $mockDriveFile = new DriveFile([
            'id' => $fileId,
            'name' => 'malicious.exe',
            'mimeType' => 'application/x-msdownload',
            'size' => 1024,
        ]);

        $mockFilesResource = Mockery::mock(DriveFilesResource::class);
        $mockFilesResource->shouldReceive('get')
            ->once()
            ->with($fileId, Mockery::type('array'))
            ->andReturn($mockDriveFile);

        $mockDrive = Mockery::mock(GoogleDrive::class);
        $mockDrive->files = $mockFilesResource;

        $this->driveService->setDriveService($mockDrive);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage("tidak diizinkan untuk di-preview");

        $this->driveService->getFileStream($fileId);
    }

    public function test_get_file_stream_oversized_file_throws_exception(): void
    {
        $fileId = '1a2B3c4D5e6F7g8H9i0J';

        $mockDriveFile = new DriveFile([
            'id' => $fileId,
            'name' => 'huge_contract.pdf',
            'mimeType' => 'application/pdf',
            'size' => 50 * 1024 * 1024, // 50 MB (> 25 MB limit)
        ]);

        $mockFilesResource = Mockery::mock(DriveFilesResource::class);
        $mockFilesResource->shouldReceive('get')
            ->once()
            ->with($fileId, Mockery::type('array'))
            ->andReturn($mockDriveFile);

        $mockDrive = Mockery::mock(GoogleDrive::class);
        $mockDrive->files = $mockFilesResource;

        $this->driveService->setDriveService($mockDrive);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage("melebihi batas preview maksimum");

        $this->driveService->getFileStream($fileId);
    }
}

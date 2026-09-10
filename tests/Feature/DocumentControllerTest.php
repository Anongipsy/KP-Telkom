<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\AuditLog;
use App\Models\User;
use App\Services\ContractService;
use App\Services\GoogleDriveService;
use GuzzleHttp\Psr7\Utils;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class DocumentControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function getSampleContract(string $lop = 'LOP-101', ?string $docRef = '1a2B3c4D5e6F7g8H9i0J'): array
    {
        return [
            '_row_index' => 2,
            'lop' => $lop,
            'contract_number' => 'CTR/101',
            'customer' => 'PT Telekomunikasi Selular',
            'satker' => 'Satker Jakarta',
            'service' => 'Astinet',
            'stage' => 'F3',
            'stage_label' => 'Bidding',
            'revenue' => 150000000,
            'revenue_formatted' => 'Rp 150.000.000',
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
            'days_remaining' => 90,
            'expiration_status' => 'ACTIVE',
            'sp_po' => 'AVAILABLE',
            'invoice_status' => 'ISSUED',
            'invoice_status_label' => 'Issued',
            'billcomp_status' => 'PARTIAL',
            'billcomp_status_label' => 'Partial',
            'billcomp_percentage' => 50,
            'realized_revenue' => 75000000,
            'realized_revenue_formatted' => 'Rp 75.000.000',
            'document_reference' => $docRef,
        ];
    }

    public function test_guest_cannot_access_document_endpoints(): void
    {
        $this->getJson('/contracts/LOP-101/document/metadata')->assertUnauthorized();
        $this->get('/contracts/LOP-101/document/proxy')->assertRedirect('/login');
    }

    public function test_metadata_endpoint_returns_file_info_when_available(): void
    {
        $user = User::factory()->create(['role' => UserRole::AM]);

        $mockContractService = Mockery::mock(ContractService::class);
        $mockContractService->shouldReceive('findByLop')
            ->once()
            ->with('LOP-101')
            ->andReturn($this->getSampleContract('LOP-101', '1a2B3c4D5e6F7g8H9i0J'));

        $mockDriveService = Mockery::mock(GoogleDriveService::class);
        $mockDriveService->shouldReceive('verifyFileAvailability')
            ->once()
            ->with('1a2B3c4D5e6F7g8H9i0J')
            ->andReturn([
                'available' => true,
                'status' => 'available',
                'metadata' => [
                    'id' => '1a2B3c4D5e6F7g8H9i0J',
                    'name' => 'Kontrak_Telkom.pdf',
                    'mime_type' => 'application/pdf',
                    'size' => 1048576,
                    'size_formatted' => '1 MB',
                    'is_pdf' => true,
                    'is_image' => false,
                    'is_previewable' => true,
                    'preview_url' => 'https://drive.google.com/file/d/1a2B3c4D5e6F7g8H9i0J/preview',
                ],
                'error' => null,
            ]);

        $this->app->instance(ContractService::class, $mockContractService);
        $this->app->instance(GoogleDriveService::class, $mockDriveService);

        $response = $this->actingAs($user)->getJson('/contracts/LOP-101/document/metadata');

        $response->assertOk();
        $response->assertJson([
            'success' => true,
            'status' => 'available',
            'lop' => 'LOP-101',
            'metadata' => [
                'id' => '1a2B3c4D5e6F7g8H9i0J',
                'name' => 'Kontrak_Telkom.pdf',
                'is_pdf' => true,
            ],
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'document_view_metadata',
            'user_id' => $user->id,
            'target_reference' => 'LOP-101',
        ]);
    }

    public function test_metadata_endpoint_returns_404_when_contract_not_found(): void
    {
        $user = User::factory()->create(['role' => UserRole::AM]);

        $mockContractService = Mockery::mock(ContractService::class);
        $mockContractService->shouldReceive('findByLop')
            ->once()
            ->with('LOP-NONEXISTENT')
            ->andReturn(null);

        $this->app->instance(ContractService::class, $mockContractService);

        $response = $this->actingAs($user)->getJson('/contracts/LOP-NONEXISTENT/document/metadata');

        $response->assertNotFound();
        $response->assertJson([
            'success' => false,
            'status' => 'not_found',
        ]);
    }

    public function test_metadata_endpoint_handles_missing_document_reference(): void
    {
        $user = User::factory()->create(['role' => UserRole::AM]);

        $mockContractService = Mockery::mock(ContractService::class);
        $mockContractService->shouldReceive('findByLop')
            ->once()
            ->with('LOP-101')
            ->andReturn($this->getSampleContract('LOP-101', null));

        $this->app->instance(ContractService::class, $mockContractService);

        $response = $this->actingAs($user)->getJson('/contracts/LOP-101/document/metadata');

        $response->assertStatus(400);
        $response->assertJson([
            'success' => false,
            'status' => 'invalid_document',
        ]);
    }

    public function test_metadata_endpoint_handles_drive_file_not_found(): void
    {
        $user = User::factory()->create(['role' => UserRole::AM]);

        $mockContractService = Mockery::mock(ContractService::class);
        $mockContractService->shouldReceive('findByLop')
            ->once()
            ->with('LOP-101')
            ->andReturn($this->getSampleContract('LOP-101', '1a2B3c4D5e6F7g8H9i0J'));

        $mockDriveService = Mockery::mock(GoogleDriveService::class);
        $mockDriveService->shouldReceive('verifyFileAvailability')
            ->once()
            ->with('1a2B3c4D5e6F7g8H9i0J')
            ->andReturn([
                'available' => false,
                'status' => 'not_found',
                'metadata' => null,
                'error' => 'File tidak ditemukan di Google Drive.',
            ]);

        $this->app->instance(ContractService::class, $mockContractService);
        $this->app->instance(GoogleDriveService::class, $mockDriveService);

        $response = $this->actingAs($user)->getJson('/contracts/LOP-101/document/metadata');

        $response->assertOk();
        $response->assertJson([
            'success' => false,
            'status' => 'not_found',
        ]);
    }

    public function test_proxy_endpoint_streams_file_content_successfully(): void
    {
        $user = User::factory()->create(['role' => UserRole::AM]);

        $mockContractService = Mockery::mock(ContractService::class);
        $mockContractService->shouldReceive('findByLop')
            ->once()
            ->with('LOP-101')
            ->andReturn($this->getSampleContract('LOP-101', '1a2B3c4D5e6F7g8H9i0J'));

        $mockDriveService = Mockery::mock(GoogleDriveService::class);
        $mockDriveService->shouldReceive('parseDocumentReference')
            ->once()
            ->with('1a2B3c4D5e6F7g8H9i0J')
            ->andReturn('1a2B3c4D5e6F7g8H9i0J');

        $mockDriveService->shouldReceive('getFileStream')
            ->once()
            ->with('1a2B3c4D5e6F7g8H9i0J')
            ->andReturn([
                'stream' => Utils::streamFor('%PDF-1.4 Mock PDF Content'),
                'mime_type' => 'application/pdf',
                'name' => 'Kontrak_Telkom.pdf',
                'size' => 28,
            ]);

        $this->app->instance(ContractService::class, $mockContractService);
        $this->app->instance(GoogleDriveService::class, $mockDriveService);

        $response = $this->actingAs($user)->get('/contracts/LOP-101/document/proxy');

        $response->assertOk();
        $response->assertHeader('Content-Type', 'application/pdf');
        $response->assertHeader('Content-Disposition', 'inline; filename="Kontrak_Telkom.pdf"');

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'document_view_stream',
            'user_id' => $user->id,
            'target_reference' => 'LOP-101',
        ]);
    }

    public function test_proxy_endpoint_returns_404_when_contract_not_found(): void
    {
        $user = User::factory()->create(['role' => UserRole::AM]);

        $mockContractService = Mockery::mock(ContractService::class);
        $mockContractService->shouldReceive('findByLop')
            ->once()
            ->with('LOP-NONEXISTENT')
            ->andReturn(null);

        $this->app->instance(ContractService::class, $mockContractService);

        $response = $this->actingAs($user)->get('/contracts/LOP-NONEXISTENT/document/proxy');

        $response->assertNotFound();
    }
}

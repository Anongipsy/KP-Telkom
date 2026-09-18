<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\AuditLog;
use App\Models\ContractDocument;
use App\Models\User;
use App\Services\ContractService;
use App\Services\DocumentStorageService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Mockery;
use Tests\TestCase;

class DocumentControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
    }

    protected function getSampleContract(string $lop = 'LOP-101'): array
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
            'document_reference' => '',
        ];
    }

    public function test_guest_cannot_access_document_endpoints(): void
    {
        $this->getJson('/contracts/LOP-101/document/metadata')->assertUnauthorized();
        $this->get('/contracts/LOP-101/document/proxy')->assertRedirect('/login');
        $this->post('/contracts/LOP-101/document/upload', [])->assertRedirect('/login');
        $this->delete('/contracts/LOP-101/document')->assertRedirect('/login');
    }

    public function test_metadata_endpoint_returns_file_info_when_document_exists(): void
    {
        $user = User::factory()->create(['role' => UserRole::AM]);

        $mockContractService = Mockery::mock(ContractService::class);
        $mockContractService->shouldReceive('findByLop')
            ->once()
            ->with('LOP-101')
            ->andReturn($this->getSampleContract('LOP-101'));

        $this->app->instance(ContractService::class, $mockContractService);

        // Upload document via service
        $file = UploadedFile::fake()->create('Kontrak_Telkom.pdf', 1024, 'application/pdf');
        app(DocumentStorageService::class)->upload($file, 'LOP-101', $user->id);

        $response = $this->actingAs($user)->getJson('/contracts/LOP-101/document/metadata');

        $response->assertOk();
        $response->assertJson([
            'success' => true,
            'status' => 'available',
            'lop' => 'LOP-101',
            'metadata' => [
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

    public function test_metadata_endpoint_handles_missing_document(): void
    {
        $user = User::factory()->create(['role' => UserRole::AM]);

        $mockContractService = Mockery::mock(ContractService::class);
        $mockContractService->shouldReceive('findByLop')
            ->once()
            ->with('LOP-101')
            ->andReturn($this->getSampleContract('LOP-101'));

        $this->app->instance(ContractService::class, $mockContractService);

        $response = $this->actingAs($user)->getJson('/contracts/LOP-101/document/metadata');

        $response->assertNotFound();
        $response->assertJson([
            'success' => false,
            'status' => 'not_found',
        ]);
    }

    public function test_proxy_endpoint_streams_file_content(): void
    {
        $user = User::factory()->create(['role' => UserRole::AM]);

        $mockContractService = Mockery::mock(ContractService::class);
        $mockContractService->shouldReceive('findByLop')
            ->once()
            ->with('LOP-101')
            ->andReturn($this->getSampleContract('LOP-101'));

        $this->app->instance(ContractService::class, $mockContractService);

        $file = UploadedFile::fake()->create('Kontrak_Resmi.pdf', 512, 'application/pdf');
        app(DocumentStorageService::class)->upload($file, 'LOP-101', $user->id);

        $response = $this->actingAs($user)->get('/contracts/LOP-101/document/proxy');

        $response->assertOk();
        $response->assertHeader('Content-Type', 'application/pdf');
        $this->assertStringContainsString('inline', $response->headers->get('Content-Disposition') ?? '');

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'document_view_stream',
            'user_id' => $user->id,
            'target_reference' => 'LOP-101',
        ]);
    }

    public function test_proxy_endpoint_aborts_when_document_missing(): void
    {
        $user = User::factory()->create(['role' => UserRole::AM]);

        $mockContractService = Mockery::mock(ContractService::class);
        $mockContractService->shouldReceive('findByLop')
            ->once()
            ->with('LOP-101')
            ->andReturn($this->getSampleContract('LOP-101'));

        $this->app->instance(ContractService::class, $mockContractService);

        $response = $this->actingAs($user)->get('/contracts/LOP-101/document/proxy');

        $response->assertNotFound();
    }

    public function test_upload_endpoint_stores_file_successfully(): void
    {
        $user = User::factory()->create(['role' => UserRole::AM]);

        $mockContractService = Mockery::mock(ContractService::class);
        $mockContractService->shouldReceive('findByLop')
            ->once()
            ->with('LOP-UPLOAD-01')
            ->andReturn($this->getSampleContract('LOP-UPLOAD-01'));

        $this->app->instance(ContractService::class, $mockContractService);

        $file = UploadedFile::fake()->create('surat_perjanjian.pdf', 1024, 'application/pdf');

        $response = $this->actingAs($user)->post('/contracts/LOP-UPLOAD-01/document/upload', [
            'document_file' => $file,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('status');

        $this->assertDatabaseHas('contract_documents', [
            'lop' => 'LOP-UPLOAD-01',
            'original_name' => 'surat_perjanjian.pdf',
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'document_upload',
            'user_id' => $user->id,
            'target_reference' => 'LOP-UPLOAD-01',
        ]);
    }

    public function test_upload_endpoint_rejects_invalid_file_format(): void
    {
        $user = User::factory()->create(['role' => UserRole::AM]);

        $file = UploadedFile::fake()->create('malicious.sh', 100, 'application/x-sh');

        $response = $this->actingAs($user)->post('/contracts/LOP-UPLOAD-01/document/upload', [
            'document_file' => $file,
        ]);

        $response->assertSessionHasErrors(['document_file']);
        $this->assertDatabaseMissing('contract_documents', [
            'lop' => 'LOP-UPLOAD-01',
        ]);
    }

    public function test_upload_endpoint_rejects_file_over_10mb(): void
    {
        $user = User::factory()->create(['role' => UserRole::AM]);

        // 12MB file (over 10MB limit)
        $file = UploadedFile::fake()->create('huge_contract.pdf', 12 * 1024, 'application/pdf');

        $response = $this->actingAs($user)->post('/contracts/LOP-UPLOAD-01/document/upload', [
            'document_file' => $file,
        ]);

        $response->assertSessionHasErrors(['document_file']);
    }

    public function test_destroy_endpoint_deletes_document_and_records_audit(): void
    {
        $user = User::factory()->create(['role' => UserRole::AM]);

        $mockContractService = Mockery::mock(ContractService::class);
        $mockContractService->shouldReceive('findByLop')
            ->once()
            ->with('LOP-DEL-01')
            ->andReturn($this->getSampleContract('LOP-DEL-01'));

        $this->app->instance(ContractService::class, $mockContractService);

        $file = UploadedFile::fake()->create('doc_to_delete.pdf', 500, 'application/pdf');
        app(DocumentStorageService::class)->upload($file, 'LOP-DEL-01', $user->id);

        $this->assertDatabaseHas('contract_documents', ['lop' => 'LOP-DEL-01']);

        $response = $this->actingAs($user)->delete('/contracts/LOP-DEL-01/document');

        $response->assertRedirect();
        $response->assertSessionHas('status');

        $this->assertDatabaseMissing('contract_documents', ['lop' => 'LOP-DEL-01']);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'document_delete',
            'user_id' => $user->id,
            'target_reference' => 'LOP-DEL-01',
        ]);
    }
}

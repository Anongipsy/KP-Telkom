<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\AuditLog;
use App\Models\User;
use App\Services\ContractService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use RuntimeException;
use Tests\TestCase;

class ContractCrudTest extends TestCase
{
    use RefreshDatabase;

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
            'end_date' => Carbon::now()->addDays(90)->format('Y-m-d'),
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
            'document_reference' => 'drive_id_101',
        ];
    }

    public function test_guest_cannot_access_contract_pages(): void
    {
        $this->get('/contracts/create')->assertRedirect('/login');
        $this->get('/contracts/LOP-101')->assertRedirect('/login');
        $this->get('/contracts/LOP-101/edit')->assertRedirect('/login');
        $this->post('/contracts', [])->assertRedirect('/login');
        $this->put('/contracts/LOP-101', [])->assertRedirect('/login');
    }

    public function test_user_can_view_create_form(): void
    {
        $user = User::factory()->create(['role' => UserRole::AM]);

        $response = $this->actingAs($user)->get('/contracts/create');

        $response->assertOk();
        $response->assertViewIs('contracts.create');
        $response->assertSee('Tambah Kontrak Kerja B2B');
        $response->assertSee('Nomor LOP (Unique Identifier)');
    }

    public function test_user_can_view_contract_detail(): void
    {
        $user = User::factory()->create(['role' => UserRole::AM]);

        $mockContractService = Mockery::mock(ContractService::class);
        $mockContractService->shouldReceive('findByLop')
            ->once()
            ->with('LOP-101')
            ->andReturn($this->getSampleContract('LOP-101'));

        $this->app->instance(ContractService::class, $mockContractService);

        $response = $this->actingAs($user)->get('/contracts/LOP-101');

        $response->assertOk();
        $response->assertViewIs('contracts.show');
        $response->assertSee('LOP-101');
        $response->assertSee('PT Telekomunikasi Selular');
        $response->assertSee('Rp 150.000.000');
        $response->assertSee('Astinet');
    }

    public function test_contract_not_found_redirects_to_dashboard(): void
    {
        $user = User::factory()->create(['role' => UserRole::AM]);

        $mockContractService = Mockery::mock(ContractService::class);
        $mockContractService->shouldReceive('findByLop')
            ->once()
            ->with('LOP-NONEXISTENT')
            ->andReturn(null);

        $this->app->instance(ContractService::class, $mockContractService);

        $response = $this->actingAs($user)->get('/contracts/LOP-NONEXISTENT');

        $response->assertRedirect('/dashboard');
        $response->assertSessionHas('error');
    }

    public function test_user_can_create_valid_contract(): void
    {
        $user = User::factory()->create(['role' => UserRole::AM]);

        $inputData = [
            'lop' => 'LOP-NEW-999',
            'contract_number' => 'CTR/999',
            'customer' => 'PT Bank Mandiri Tbk',
            'satker' => 'Satker IT',
            'service' => 'SD-WAN',
            'stage' => 'F2',
            'revenue' => 'Rp 200.000.000',
            'start_date' => '2026-03-01',
            'end_date' => '2026-12-31',
            'sp_po' => 'AVAILABLE',
            'invoice_status' => 'UNBILLED',
            'billcomp_status' => 'NOT_COMPLETE',
            'billcomp_percentage' => '0',
            'document_reference' => 'drive_ref_999',
        ];

        $mockContractService = Mockery::mock(ContractService::class);
        $mockContractService->shouldReceive('createContract')
            ->once()
            ->andReturn($this->getSampleContract('LOP-NEW-999'));

        $this->app->instance(ContractService::class, $mockContractService);

        $response = $this->actingAs($user)->post('/contracts', $inputData);

        $response->assertRedirect('/contracts/LOP-NEW-999');
        $response->assertSessionHas('status');
    }

    public function test_create_contract_validation_errors(): void
    {
        $user = User::factory()->create(['role' => UserRole::AM]);

        // Missing required fields (customer, service, revenue)
        $invalidData = [
            'lop' => 'LOP-INVALID',
            'customer' => '',
            'service' => '',
            'stage' => 'INVALID_STAGE',
            'revenue' => '',
        ];

        $response = $this->actingAs($user)->post('/contracts', $invalidData);

        $response->assertSessionHasErrors(['customer', 'service', 'stage', 'revenue']);
    }

    public function test_user_can_view_edit_form(): void
    {
        $user = User::factory()->create(['role' => UserRole::AM]);

        $mockContractService = Mockery::mock(ContractService::class);
        $mockContractService->shouldReceive('findByLop')
            ->once()
            ->with('LOP-101')
            ->andReturn($this->getSampleContract('LOP-101'));

        $this->app->instance(ContractService::class, $mockContractService);

        $response = $this->actingAs($user)->get('/contracts/LOP-101/edit');

        $response->assertOk();
        $response->assertViewIs('contracts.edit');
        $response->assertSee('Edit Kontrak: PT Telekomunikasi Selular');
        $response->assertSee('LOP-101');
    }

    public function test_user_can_update_valid_contract(): void
    {
        $user = User::factory()->create(['role' => UserRole::AM]);

        $updateData = [
            'contract_number' => 'CTR/101-UPDATED',
            'customer' => 'PT Telekomunikasi Selular',
            'satker' => 'Satker Jakarta Pusat',
            'service' => 'Astinet High Speed',
            'stage' => 'F4',
            'revenue' => '180000000',
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
            'sp_po' => 'AVAILABLE',
            'invoice_status' => 'ISSUED',
            'billcomp_status' => 'PARTIAL',
            'billcomp_percentage' => '75',
            'document_reference' => 'drive_id_101',
        ];

        $mockContractService = Mockery::mock(ContractService::class);
        $mockContractService->shouldReceive('updateContract')
            ->once()
            ->with('LOP-101', Mockery::type('array'), $user->id)
            ->andReturn(array_merge($this->getSampleContract('LOP-101'), ['stage' => 'F4']));

        $this->app->instance(ContractService::class, $mockContractService);

        $response = $this->actingAs($user)->put('/contracts/LOP-101', $updateData);

        $response->assertRedirect('/contracts/LOP-101');
        $response->assertSessionHas('status');
    }

    public function test_update_contract_validation_errors(): void
    {
        $user = User::factory()->create(['role' => UserRole::AM]);

        $invalidData = [
            'customer' => '',
            'service' => '',
            'stage' => 'NOT_A_VALID_STAGE',
            'revenue' => '',
        ];

        $response = $this->actingAs($user)->put('/contracts/LOP-101', $invalidData);

        $response->assertSessionHasErrors(['customer', 'service', 'stage', 'revenue']);
    }

    public function test_api_failure_on_create_redirects_with_error(): void
    {
        $user = User::factory()->create(['role' => UserRole::AM]);

        $inputData = [
            'lop' => 'LOP-ERR',
            'customer' => 'PT Test Error',
            'service' => 'Indibiz',
            'stage' => 'F1',
            'revenue' => '50000000',
        ];

        $mockContractService = Mockery::mock(ContractService::class);
        $mockContractService->shouldReceive('createContract')
            ->once()
            ->andThrow(new RuntimeException('Google API rate limit exceeded'));

        $this->app->instance(ContractService::class, $mockContractService);

        $response = $this->actingAs($user)->post('/contracts', $inputData);

        $response->assertRedirect();
        $response->assertSessionHas('error');
    }

    public function test_api_failure_on_update_redirects_with_error(): void
    {
        $user = User::factory()->create(['role' => UserRole::AM]);

        $updateData = [
            'customer' => 'PT Test Error',
            'service' => 'Indibiz',
            'stage' => 'F1',
            'revenue' => '50000000',
        ];

        $mockContractService = Mockery::mock(ContractService::class);
        $mockContractService->shouldReceive('updateContract')
            ->once()
            ->andThrow(new RuntimeException('Google Sheets row not found'));

        $this->app->instance(ContractService::class, $mockContractService);

        $response = $this->actingAs($user)->put('/contracts/LOP-ERR', $updateData);

        $response->assertRedirect();
        $response->assertSessionHas('error');
    }

    public function test_user_can_complete_contract(): void
    {
        $user = User::factory()->create(['role' => UserRole::AM]);

        $mockContractService = Mockery::mock(ContractService::class);
        $mockContractService->shouldReceive('completeContract')
            ->once()
            ->with('LOP-101', $user->id)
            ->andReturn($this->getSampleContract('LOP-101'));

        $this->app->instance(ContractService::class, $mockContractService);

        $response = $this->actingAs($user)->post('/contracts/LOP-101/complete');

        $response->assertRedirect('/contracts/LOP-101');
        $response->assertSessionHas('status');
    }

    public function test_contract_service_complete_contract_records_audit_log(): void
    {
        $user = User::factory()->create(['role' => UserRole::AM]);

        $mockSheets = Mockery::mock(\App\Services\GoogleSheetsService::class);
        $mockSheets->shouldReceive('findByLop')
            ->once()
            ->with('LOP-200')
            ->andReturn(array_merge($this->getSampleContract('LOP-200'), [
                'is_overdue' => true,
                'status_kontrak' => 'BERJALAN',
            ]));
        $mockSheets->shouldReceive('updateByLop')
            ->once()
            ->with('LOP-200', ['status_kontrak' => 'Kontrak Selesai'], $user->id)
            ->andReturn(array_merge($this->getSampleContract('LOP-200'), [
                'status_kontrak' => 'SELESAI',
                'status_kontrak_label' => 'Kontrak Selesai',
            ]));

        $service = new ContractService(
            $mockSheets,
            $this->app->make(\App\Services\ExpirationService::class),
            $this->app->make(\App\Services\DataTransformer::class)
        );

        $result = $service->completeContract('LOP-200', $user->id);

        $this->assertEquals('SELESAI', $result['status_kontrak']);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'contract_status_completed',
            'user_id' => $user->id,
            'target_type' => 'contract',
            'target_reference' => 'LOP-200',
        ]);
    }

    public function test_filter_contracts_by_status_kontrak(): void
    {
        $service = $this->app->make(ContractService::class);

        $contracts = [
            array_merge($this->getSampleContract('LOP-1'), ['status_kontrak' => 'BERJALAN']),
            array_merge($this->getSampleContract('LOP-2'), ['status_kontrak' => 'SELESAI']),
        ];

        $filteredBerjalan = $service->filterContracts(['status_kontrak' => 'BERJALAN'], $contracts);
        $this->assertCount(1, $filteredBerjalan);
        $this->assertEquals('LOP-1', $filteredBerjalan[0]['lop']);

        $filteredSelesai = $service->filterContracts(['status_kontrak' => 'SELESAI'], $contracts);
        $this->assertCount(1, $filteredSelesai);
        $this->assertEquals('LOP-2', $filteredSelesai[0]['lop']);

        $filteredAll = $service->filterContracts(['status_kontrak' => 'all'], $contracts);
        $this->assertCount(2, $filteredAll);
    }
}

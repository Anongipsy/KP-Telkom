<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\User;
use App\Services\ContractService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use RuntimeException;
use Tests\TestCase;

class SecurityHardeningTest extends TestCase
{
    use RefreshDatabase;

    protected User $amUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->amUser = User::factory()->create([
            'email' => 'am@telkom.co.id',
            'role' => UserRole::AM,
            'is_active' => true,
        ]);
    }

    public function test_response_includes_security_headers(): void
    {
        $response = $this->get('/login');

        $response->assertHeader('X-Content-Type-Options', 'nosniff');
        $response->assertHeader('X-Frame-Options', 'SAMEORIGIN');
        $response->assertHeader('X-XSS-Protection', '1; mode=block');
        $response->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->assertHeader('Permissions-Policy', 'camera=(), microphone=(), geolocation=()');
    }

    public function test_contract_controller_whitelists_mass_assignment_fields(): void
    {
        $inputData = [
            'lop' => 'LOP-SEC-01',
            'contract_number' => 'CTR/SEC-01',
            'customer' => 'PT Telkom Indonesia',
            'satker' => 'Satker Security',
            'service' => 'Cyber Security',
            'stage' => 'F2',
            'revenue' => '500000000',
            'injected_malicious_field' => 'should_be_stripped',
            '_extra_hidden_token' => 'attacker_payload',
        ];

        $mockContractService = Mockery::mock(ContractService::class);
        $mockContractService->shouldReceive('createContract')
            ->once()
            ->withArgs(function ($data, $userId) {
                // Ensure injected fields are NOT passed to service
                return !isset($data['injected_malicious_field'])
                    && !isset($data['_extra_hidden_token'])
                    && isset($data['lop'])
                    && $data['lop'] === 'LOP-SEC-01';
            })
            ->andReturn([
                'lop' => 'LOP-SEC-01',
                'customer' => 'PT Telkom Indonesia',
                'stage' => 'F2',
            ]);

        $this->app->instance(ContractService::class, $mockContractService);

        $response = $this->actingAs($this->amUser)->post('/contracts', $inputData);

        $response->assertRedirect('/contracts/LOP-SEC-01');
    }

    public function test_controller_does_not_leak_internal_exception_details_to_user(): void
    {
        $inputData = [
            'lop' => 'LOP-SECRET-FAIL',
            'customer' => 'PT Internal Fail',
            'service' => 'Cloud',
            'stage' => 'F1',
            'revenue' => '10000000',
        ];

        $mockContractService = Mockery::mock(ContractService::class);
        $mockContractService->shouldReceive('createContract')
            ->once()
            ->andThrow(new RuntimeException('Internal Secret Exception: MySQL Access Denied for root@10.0.0.5'));

        $this->app->instance(ContractService::class, $mockContractService);

        $response = $this->actingAs($this->amUser)->post('/contracts', $inputData);

        $response->assertRedirect();
        $response->assertSessionHas('error');

        // Confirm internal trace message is NOT exposed in the session flash error
        $flashError = session('error');
        $this->assertStringNotContainsString('MySQL Access Denied for root@10.0.0.5', $flashError);
        $this->assertStringContainsString('Gagal menyimpan kontrak ke Google Sheets', $flashError);
    }
}

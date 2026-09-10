<?php

namespace Tests\Unit;

use App\Enums\UserRole;
use App\Enums\Stage;
use App\Enums\InvoiceStatus;
use App\Enums\BillcompStatus;
use App\Enums\ContractStatus;
use App\Enums\ExpirationStatus;
use PHPUnit\Framework\TestCase;

class EnumTest extends TestCase
{
    // --- UserRole ---

    public function test_user_role_has_admin_and_am(): void
    {
        $this->assertEquals('admin', UserRole::ADMIN->value);
        $this->assertEquals('am', UserRole::AM->value);
        $this->assertCount(2, UserRole::cases());
    }

    public function test_user_role_labels(): void
    {
        $this->assertEquals('Admin / Developer', UserRole::ADMIN->label());
        $this->assertEquals('Account Manager', UserRole::AM->label());
    }

    // --- Stage ---

    public function test_stage_has_f0_to_f4(): void
    {
        $this->assertEquals('F0', Stage::F0->value);
        $this->assertEquals('F1', Stage::F1->value);
        $this->assertEquals('F2', Stage::F2->value);
        $this->assertEquals('F3', Stage::F3->value);
        $this->assertEquals('F4', Stage::F4->value);
        $this->assertCount(5, Stage::cases());
    }

    public function test_stage_labels(): void
    {
        $this->assertEquals('Lead', Stage::F0->label());
        $this->assertEquals('Opportunity', Stage::F1->label());
        $this->assertEquals('Quote', Stage::F2->label());
        $this->assertEquals('Bidding', Stage::F3->label());
        $this->assertEquals('Negotiation', Stage::F4->label());
    }

    public function test_stage_full_labels(): void
    {
        $this->assertEquals('F0 Lead', Stage::F0->fullLabel());
        $this->assertEquals('F4 Negotiation', Stage::F4->fullLabel());
    }

    // --- InvoiceStatus ---

    public function test_invoice_status_values(): void
    {
        $this->assertEquals('UNBILLED', InvoiceStatus::UNBILLED->value);
        $this->assertEquals('ISSUED', InvoiceStatus::ISSUED->value);
        $this->assertEquals('PAID', InvoiceStatus::PAID->value);
        $this->assertCount(3, InvoiceStatus::cases());
    }

    // --- BillcompStatus ---

    public function test_billcomp_status_values(): void
    {
        $this->assertEquals('NOT_COMPLETE', BillcompStatus::NOT_COMPLETE->value);
        $this->assertEquals('PARTIAL', BillcompStatus::PARTIAL->value);
        $this->assertEquals('COMPLETED', BillcompStatus::COMPLETED->value);
        $this->assertCount(3, BillcompStatus::cases());
    }

    // --- ExpirationStatus ---

    public function test_expiration_status_values(): void
    {
        $this->assertEquals('ACTIVE', ExpirationStatus::ACTIVE->value);
        $this->assertEquals('EXPIRING_SOON', ExpirationStatus::EXPIRING_SOON->value);
        $this->assertEquals('OVERDUE', ExpirationStatus::OVERDUE->value);
        $this->assertCount(3, ExpirationStatus::cases());
    }

    // --- ContractStatus ---

    public function test_contract_status_values(): void
    {
        $this->assertEquals('BERJALAN', ContractStatus::BERJALAN->value);
        $this->assertEquals('SELESAI', ContractStatus::SELESAI->value);
        $this->assertCount(2, ContractStatus::cases());
    }

    public function test_contract_status_labels(): void
    {
        $this->assertEquals('Kontrak Berjalan', ContractStatus::BERJALAN->label());
        $this->assertEquals('Kontrak Selesai', ContractStatus::SELESAI->label());
    }

    public function test_contract_status_colors_and_classes(): void
    {
        $this->assertEquals('blue', ContractStatus::BERJALAN->color());
        $this->assertEquals('emerald', ContractStatus::SELESAI->color());
        $this->assertNotEmpty(ContractStatus::BERJALAN->badgeClasses());
        $this->assertNotEmpty(ContractStatus::SELESAI->badgeClasses());
    }
}

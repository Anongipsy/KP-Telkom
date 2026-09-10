<?php

namespace Tests\Unit;

use App\Enums\ExpirationStatus;
use App\Services\ExpirationService;
use Carbon\Carbon;
use PHPUnit\Framework\TestCase;

class ExpirationServiceTest extends TestCase
{
    protected ExpirationService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new ExpirationService();
    }

    public function test_calculate_days_remaining(): void
    {
        $today = Carbon::parse('2026-08-28');

        // 90 days ahead -> 90
        $this->assertEquals(90, $this->service->calculateDaysRemaining('2026-11-26', $today));

        // 30 days ahead -> 30
        $this->assertEquals(30, $this->service->calculateDaysRemaining('2026-09-27', $today));

        // Same day -> 0
        $this->assertEquals(0, $this->service->calculateDaysRemaining('2026-08-28', $today));

        // 10 days past -> -10
        $this->assertEquals(-10, $this->service->calculateDaysRemaining('2026-08-18', $today));

        // Null end date -> null
        $this->assertNull($this->service->calculateDaysRemaining(null, $today));
    }

    public function test_get_expiration_status(): void
    {
        // > 60 days -> ACTIVE
        $this->assertEquals(ExpirationStatus::ACTIVE, $this->service->getExpirationStatus(61));
        $this->assertEquals(ExpirationStatus::ACTIVE, $this->service->getExpirationStatus(120));

        // 0 to 60 days -> EXPIRING_SOON
        $this->assertEquals(ExpirationStatus::EXPIRING_SOON, $this->service->getExpirationStatus(60));
        $this->assertEquals(ExpirationStatus::EXPIRING_SOON, $this->service->getExpirationStatus(30));
        $this->assertEquals(ExpirationStatus::EXPIRING_SOON, $this->service->getExpirationStatus(0));

        // < 0 days -> OVERDUE
        $this->assertEquals(ExpirationStatus::OVERDUE, $this->service->getExpirationStatus(-1));
        $this->assertEquals(ExpirationStatus::OVERDUE, $this->service->getExpirationStatus(-50));

        // null -> null
        $this->assertNull($this->service->getExpirationStatus(null));
    }

    public function test_enrich_contract(): void
    {
        $today = Carbon::parse('2026-08-28');

        $activeContract = [
            'lop' => 'LOP-001',
            'end_date' => '2026-12-31', // ~125 days ahead
        ];

        $enrichedActive = $this->service->enrichContract($activeContract, $today);
        $this->assertEquals('ACTIVE', $enrichedActive['expiration_status']);
        $this->assertTrue($enrichedActive['is_active_contract']);
        $this->assertFalse($enrichedActive['is_expiring_soon']);
        $this->assertFalse($enrichedActive['is_overdue']);

        $expiringContract = [
            'lop' => 'LOP-002',
            'end_date' => '2026-09-15', // 18 days ahead
        ];

        $enrichedExpiring = $this->service->enrichContract($expiringContract, $today);
        $this->assertEquals('EXPIRING_SOON', $enrichedExpiring['expiration_status']);
        $this->assertEquals(18, $enrichedExpiring['days_remaining']);
        $this->assertTrue($enrichedExpiring['is_expiring_soon']);

        $overdueContract = [
            'lop' => 'LOP-003',
            'end_date' => '2026-08-01', // 27 days past
        ];

        $enrichedOverdue = $this->service->enrichContract($overdueContract, $today);
        $this->assertEquals('OVERDUE', $enrichedOverdue['expiration_status']);
        $this->assertEquals(-27, $enrichedOverdue['days_remaining']);
        $this->assertTrue($enrichedOverdue['is_overdue']);
    }
}

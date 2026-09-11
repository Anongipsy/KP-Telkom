<?php

namespace App\Services;

use App\Enums\ExpirationStatus;
use Carbon\Carbon;

/**
 * ExpirationService — PRD FR-10
 *
 * Centralizes all expiration calculation logic.
 *
 * Days Remaining = End Date - Current Date
 *
 * Status thresholds:
 * > 60 days   -> ACTIVE
 * 0–60 days   -> EXPIRING_SOON
 * < 0 days    -> OVERDUE
 */
class ExpirationService
{
    /**
     * Calculate days remaining between end date and a reference date (default: now).
     *
     * @param string|Carbon|null $endDate
     * @param Carbon|null $referenceDate
     * @return int|null
     */
    public function calculateDaysRemaining(string|Carbon|null $endDate, ?Carbon $referenceDate = null): ?int
    {
        if (!$endDate) {
            return null;
        }

        try {
            $end = $endDate instanceof Carbon ? $endDate->copy()->startOfDay() : Carbon::parse($endDate)->startOfDay();
            $now = ($referenceDate ?: Carbon::now())->copy()->startOfDay();

            // Using diffInDays with boolean false to get signed difference (negative if past)
            return (int) $now->diffInDays($end, false);
        } catch (\Exception) {
            return null;
        }
    }

    /**
     * Determine expiration status from days remaining.
     *
     * @param int|null $daysRemaining
     * @return ExpirationStatus|null
     */
    public function getExpirationStatus(?int $daysRemaining): ?ExpirationStatus
    {
        if ($daysRemaining === null) {
            return null;
        }

        if ($daysRemaining < 0) {
            return ExpirationStatus::OVERDUE;
        }

        if ($daysRemaining <= 60) {
            return ExpirationStatus::EXPIRING_SOON;
        }

        return ExpirationStatus::ACTIVE;
    }

    /**
     * Enrich a single contract array with calculated expiration metadata.
     *
     * @param array<string, mixed> $contract
     * @param Carbon|null $referenceDate
     * @return array<string, mixed>
     */
    public function enrichContract(array $contract, ?Carbon $referenceDate = null): array
    {
        $endDate = $contract['end_date'] ?? null;
        $daysRemaining = $this->calculateDaysRemaining($endDate, $referenceDate);
        $status = $this->getExpirationStatus($daysRemaining);

        $isCompleted = ($contract['is_completed'] ?? false)
            || strtoupper((string) ($contract['status_kontrak'] ?? '')) === 'SELESAI'
            || str_contains(strtoupper((string) ($contract['status_kontrak'] ?? '')), 'SELESAI');

        $contract['days_remaining'] = $daysRemaining;
        $contract['expiration_status'] = $status?->value;
        $contract['expiration_status_label'] = $status?->label();
        $contract['is_active_contract'] = ($status === ExpirationStatus::ACTIVE) && !$isCompleted;
        $contract['is_expiring_soon'] = ($status === ExpirationStatus::EXPIRING_SOON) && !$isCompleted;
        $contract['is_overdue'] = ($status === ExpirationStatus::OVERDUE) && !$isCompleted;

        return $contract;
    }

    /**
     * Enrich a list of contracts.
     *
     * @param array<int, array<string, mixed>> $contracts
     * @param Carbon|null $referenceDate
     * @return array<int, array<string, mixed>>
     */
    public function enrichAll(array $contracts, ?Carbon $referenceDate = null): array
    {
        return array_map(
            fn (array $contract) => $this->enrichContract($contract, $referenceDate),
            $contracts
        );
    }
}

<?php

namespace App\Enums;

/**
 * Expiration Status enum — PRD FR-10
 *
 * > 60 days  → ACTIVE
 * 0-60 days  → EXPIRING_SOON
 * < 0 days   → OVERDUE
 */
enum ExpirationStatus: string
{
    case ACTIVE = 'ACTIVE';
    case EXPIRING_SOON = 'EXPIRING_SOON';
    case OVERDUE = 'OVERDUE';

    public function label(): string
    {
        return match ($this) {
            self::ACTIVE => 'Active',
            self::EXPIRING_SOON => 'Expiring Soon',
            self::OVERDUE => 'Overdue',
        };
    }
}

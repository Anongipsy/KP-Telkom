<?php

namespace App\Enums;

/**
 * Billcomp Status enum — PRD FR-09 & FR-15
 */
enum BillcompStatus: string
{
    case NOT_COMPLETE = 'NOT_COMPLETE';
    case PARTIAL = 'PARTIAL';
    case COMPLETED = 'COMPLETED';

    public function label(): string
    {
        return match ($this) {
            self::NOT_COMPLETE => 'Not Complete',
            self::PARTIAL => 'Partial',
            self::COMPLETED => 'Completed',
        };
    }
}

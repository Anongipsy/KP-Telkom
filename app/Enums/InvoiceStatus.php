<?php

namespace App\Enums;

/**
 * Invoice Status enum — PRD FR-09 & FR-14
 */
enum InvoiceStatus: string
{
    case UNBILLED = 'UNBILLED';
    case ISSUED = 'ISSUED';
    case PAID = 'PAID';

    public function label(): string
    {
        return match ($this) {
            self::UNBILLED => 'Unbilled',
            self::ISSUED => 'Issued',
            self::PAID => 'Paid',
        };
    }
}

<?php

namespace App\Enums;

/**
 * Pipeline Stage enum — PRD FR-04
 *
 * F0 Lead → F1 Opportunity → F2 Quote → F3 Bidding → F4 Negotiation
 */
enum Stage: string
{
    case F0 = 'F0';
    case F1 = 'F1';
    case F2 = 'F2';
    case F3 = 'F3';
    case F4 = 'F4';

    public function label(): string
    {
        return match ($this) {
            self::F0 => 'Lead',
            self::F1 => 'Opportunity',
            self::F2 => 'Quote',
            self::F3 => 'Bidding',
            self::F4 => 'Negotiation',
        };
    }

    public function fullLabel(): string
    {
        return "{$this->value} {$this->label()}";
    }
}

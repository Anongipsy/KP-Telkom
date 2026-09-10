<?php

namespace App\Enums;

/**
 * Contract Status enum — Kontrak Berjalan vs Kontrak Selesai
 */
enum ContractStatus: string
{
    case BERJALAN = 'BERJALAN';
    case SELESAI = 'SELESAI';

    public function label(): string
    {
        return match ($this) {
            self::BERJALAN => 'Kontrak Berjalan',
            self::SELESAI => 'Kontrak Selesai',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::BERJALAN => 'blue',
            self::SELESAI => 'emerald',
        };
    }

    public function badgeClasses(): string
    {
        return match ($this) {
            self::BERJALAN => 'bg-blue-50 text-blue-700 border-blue-200 dark:bg-blue-900/30 dark:text-blue-300 dark:border-blue-800',
            self::SELESAI => 'bg-emerald-50 text-emerald-700 border-emerald-200 dark:bg-emerald-900/30 dark:text-emerald-300 dark:border-emerald-800',
        };
    }
}

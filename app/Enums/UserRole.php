<?php

namespace App\Enums;

/**
 * User Role enum — PRD FR-02
 *
 * ADMIN: Developer/maintenance — monitoring, logs, troubleshooting
 * AM: Account Manager — primary user, dashboard, contracts
 */
enum UserRole: string
{
    case ADMIN = 'admin';
    case AM = 'am';

    public function label(): string
    {
        return match ($this) {
            self::ADMIN => 'Admin / Developer',
            self::AM => 'Account Manager',
        };
    }
}

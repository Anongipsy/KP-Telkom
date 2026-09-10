<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\User;
use App\Models\ApplicationSetting;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     *
     * PRD Section 4:
     * - 1 AM user (primary user / mentor)
     * - 1 Admin user (developer/maintenance)
     */
    public function run(): void
    {
        // Create AM user — PRD Section 4.1
        User::factory()->create([
            'name' => 'Account Manager',
            'email' => 'am@telkom.co.id',
            'role' => UserRole::AM,
            'is_active' => true,
        ]);

        // Create Admin/Developer user — PRD Section 4.2
        User::factory()->create([
            'name' => 'Developer',
            'email' => 'admin@telkom.co.id',
            'role' => UserRole::ADMIN,
            'is_active' => true,
        ]);

        // Default application settings
        ApplicationSetting::setValue('app_version', '1.0.0');
        ApplicationSetting::setValue('sheets_cache_ttl', '300');
    }
}

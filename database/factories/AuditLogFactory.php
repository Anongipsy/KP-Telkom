<?php

namespace Database\Factories;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AuditLog>
 */
class AuditLogFactory extends Factory
{
    protected $model = AuditLog::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'action' => fake()->randomElement([
                'login',
                'failed_login',
                'contract_update',
                'contract_create',
                'google_sheets_error',
                'google_drive_error',
                'notification_generated',
            ]),
            'target_type' => fake()->optional()->randomElement(['contract', 'user', 'notification']),
            'target_reference' => fake()->optional()->numerify('LOP-####'),
            'metadata' => null,
        ];
    }

    /**
     * Create a login audit log.
     */
    public function login(): static
    {
        return $this->state(fn (array $attributes) => [
            'action' => 'login',
            'target_type' => 'user',
            'metadata' => ['ip' => fake()->ipv4()],
        ]);
    }

    /**
     * Create a failed login audit log.
     */
    public function failedLogin(): static
    {
        return $this->state(fn (array $attributes) => [
            'action' => 'failed_login',
            'target_type' => 'user',
            'metadata' => ['ip' => fake()->ipv4(), 'reason' => 'invalid_credentials'],
        ]);
    }

    /**
     * Create a contract update audit log.
     */
    public function contractUpdate(): static
    {
        return $this->state(fn (array $attributes) => [
            'action' => 'contract_update',
            'target_type' => 'contract',
            'target_reference' => fake()->numerify('LOP-####'),
        ]);
    }
}

<?php

namespace Database\Factories;

use App\Models\SyncLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SyncLog>
 */
class SyncLogFactory extends Factory
{
    protected $model = SyncLog::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $startedAt = fake()->dateTimeBetween('-7 days', 'now');
        $completedAt = (clone $startedAt)->modify('+' . fake()->numberBetween(1, 30) . ' seconds');

        return [
            'user_id' => User::factory(),
            'sync_type' => fake()->randomElement(['contracts', 'documents']),
            'direction' => fake()->randomElement(['SHEETS_TO_APP', 'APP_TO_SHEETS']),
            'status' => fake()->randomElement(['success', 'failed', 'partial']),
            'records_processed' => fake()->numberBetween(0, 100),
            'error_message' => null,
            'started_at' => $startedAt,
            'completed_at' => $completedAt,
        ];
    }

    /**
     * Set sync as successful.
     */
    public function successful(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'success',
            'error_message' => null,
        ]);
    }

    /**
     * Set sync as failed.
     */
    public function failed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'failed',
            'records_processed' => 0,
            'error_message' => fake()->sentence(),
        ]);
    }

    /**
     * Set direction to SHEETS_TO_APP.
     */
    public function fromSheets(): static
    {
        return $this->state(fn (array $attributes) => [
            'direction' => 'SHEETS_TO_APP',
        ]);
    }

    /**
     * Set direction to APP_TO_SHEETS.
     */
    public function toSheets(): static
    {
        return $this->state(fn (array $attributes) => [
            'direction' => 'APP_TO_SHEETS',
        ]);
    }
}

<?php

namespace Database\Factories;

use App\Models\Notification;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Notification>
 */
class NotificationFactory extends Factory
{
    protected $model = Notification::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'lop_reference' => 'LOP-' . fake()->unique()->numerify('####'),
            'alert_type' => fake()->randomElement(['EXPIRING_SOON', 'OVERDUE']),
            'days_remaining' => fake()->numberBetween(-30, 60),
            'is_read' => false,
        ];
    }

    /**
     * Mark notification as read.
     */
    public function read(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_read' => true,
        ]);
    }

    /**
     * Set alert type to EXPIRING_SOON.
     */
    public function expiringSoon(): static
    {
        return $this->state(fn (array $attributes) => [
            'alert_type' => 'EXPIRING_SOON',
            'days_remaining' => fake()->numberBetween(1, 60),
        ]);
    }

    /**
     * Set alert type to OVERDUE.
     */
    public function overdue(): static
    {
        return $this->state(fn (array $attributes) => [
            'alert_type' => 'OVERDUE',
            'days_remaining' => fake()->numberBetween(-60, -1),
        ]);
    }
}

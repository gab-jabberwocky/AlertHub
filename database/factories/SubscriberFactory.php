<?php

namespace Database\Factories;

use App\Models\Project;
use App\Models\Subscriber;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Subscriber>
 */
class SubscriberFactory extends Factory
{
    public function definition(): array
    {
        return [
            'project_id' => Project::factory(),
            'email' => $this->faker->optional(0.8)->safeEmail(),
            'external_id' => $this->faker->optional(0.7)->bothify('external_##??'),
            'name' => $this->faker->name(),
            'notification_count' => $this->faker->numberBetween(0, 20),
            'last_notified_at' => $this->faker->optional()->dateTimeBetween('-30 days', 'now'),
            'metadata' => [
                'source' => $this->faker->randomElement(['github', 'stripe', 'monitoring', 'custom']),
            ],
        ];
    }
}

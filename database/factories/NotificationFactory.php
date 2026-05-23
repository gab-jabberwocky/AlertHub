<?php

namespace Database\Factories;

use App\Models\AlertRule;
use App\Models\Notification;
use App\Models\Project;
use App\Models\Subscriber;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Notification>
 */
class NotificationFactory extends Factory
{
    public function definition(): array
    {
        return [
            'uuid' => $this->faker->uuid(),
            'project_id' => Project::factory(),
            'subscriber_id' => Subscriber::factory(),
            'alert_rule_id' => AlertRule::factory(),
            'channel' => $this->faker->randomElement(['email', 'webhook']),
            'subject' => $this->faker->sentence(),
            'body' => $this->faker->paragraph(),
            'payload' => [
                'source' => $this->faker->randomElement(['github', 'stripe', 'monitoring']),
                'details' => $this->faker->paragraph(),
            ],
            'status' => $this->faker->randomElement(['pending', 'sent', 'failed', 'escalated']),
            'sent_at' => $this->faker->optional()->dateTimeBetween('-5 days', 'now'),
        ];
    }
}

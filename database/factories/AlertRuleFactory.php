<?php

namespace Database\Factories;

use App\Models\AlertRule;
use App\Models\Project;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AlertRule>
 */
class AlertRuleFactory extends Factory
{
    public function definition(): array
    {
        return [
            'project_id' => Project::factory(),
            'name' => $this->faker->sentence(3),
            'source_type' => $this->faker->randomElement(['github', 'stripe', 'monitoring', 'custom']),
            'event_type' => $this->faker->randomElement(['push', 'payment_intent.payment_failed', 'alert.triggered', 'custom.event']),
            'conditions' => [
                'severity' => $this->faker->randomElement(['low', 'medium', 'high', 'critical']),
            ],
            'action' => $this->faker->randomElement(['notify', 'escalate', 'digest']),
            'priority' => $this->faker->randomElement(['low', 'medium', 'high', 'critical']),
            'is_active' => $this->faker->boolean(85),
        ];
    }
}

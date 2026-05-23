<?php

namespace Database\Factories;

use App\Models\Project;
use App\Models\WebhookSource;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<WebhookSource>
 */
class WebhookSourceFactory extends Factory
{
    public function definition(): array
    {
        return [
            'project_id' => Project::factory(),
            'source_key' => $this->faker->unique()->bothify('src_####??'),
            'source_type' => $this->faker->randomElement(['github', 'stripe', 'monitoring', 'custom']),
            'name' => $this->faker->company() . ' Webhook Source',
            'signing_secret' => $this->faker->optional()->sha256(),
            'event_mappings' => [
                'push' => 'push_event',
                'payment_intent.payment_failed' => 'payment_failed',
            ],
            'is_active' => $this->faker->boolean(90),
        ];
    }
}

<?php

namespace Database\Factories;

use App\Models\Organization;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Organization>
 */
class OrganizationFactory extends Factory
{
    public function definition(): array
    {
        return [
            'uuid' => $this->faker->uuid(),
            'name' => $this->faker->company(),
            'api_token' => Str::random(60),
            'plan' => $this->faker->randomElement(['free', 'pro', 'enterprise']),
            'timezone' => $this->faker->timezone(),
        ];
    }
}

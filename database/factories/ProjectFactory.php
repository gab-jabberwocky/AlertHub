<?php

namespace Database\Factories;

use App\Models\Organization;
use App\Models\Project;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Project>
 */
class ProjectFactory extends Factory
{
    public function definition(): array
    {
        return [
            'organization_id' => Organization::factory(),
            'uuid' => $this->faker->uuid(),
            'name' => $this->faker->company() . ' Project',
            'description' => $this->faker->sentence(),
        ];
    }
}

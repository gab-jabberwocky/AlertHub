<?php

namespace Database\Seeders;

use App\Models\AlertRule;
use App\Models\Project;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class AlertRuleSeeder extends Seeder
{
    public function run(): void
    {
        Project::all()->each(function (Project $project) {
            AlertRule::factory()
                ->count(2)
                ->for($project)
                ->create();
        });
    }
}

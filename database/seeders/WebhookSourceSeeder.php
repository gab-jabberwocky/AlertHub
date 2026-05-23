<?php

namespace Database\Seeders;

use App\Models\Project;
use App\Models\WebhookSource;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class WebhookSourceSeeder extends Seeder
{
    public function run(): void
    {
        Project::all()->each(function (Project $project) {
            WebhookSource::factory()
                ->count(1)
                ->for($project)
                ->create();
        });
    }
}

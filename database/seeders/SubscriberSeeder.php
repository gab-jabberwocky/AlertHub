<?php

namespace Database\Seeders;

use App\Models\Project;
use App\Models\Subscriber;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class SubscriberSeeder extends Seeder
{
    public function run(): void
    {
        Project::all()->each(function (Project $project) {
            Subscriber::factory()
                ->count(3)
                ->for($project)
                ->create();
        });
    }
}

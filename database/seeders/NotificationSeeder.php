<?php

namespace Database\Seeders;

use App\Models\Notification;
use App\Models\Project;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class NotificationSeeder extends Seeder
{
    public function run(): void
    {
        Project::with(['subscribers', 'alertRules'])->get()->each(function (Project $project) {
            $subscribers = $project->subscribers;
            $alertRules = $project->alertRules;

            if ($subscribers->isEmpty() || $alertRules->isEmpty()) {
                return;
            }

            foreach (range(1, 3) as $_) {
                Notification::factory()->create([
                    'project_id' => $project->id,
                    'subscriber_id' => $subscribers->random()->id,
                    'alert_rule_id' => $alertRules->random()->id,
                ]);
            }
        });
    }
}

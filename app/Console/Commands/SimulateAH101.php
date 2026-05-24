<?php

namespace App\Console\Commands;

use App\AlertMetrics\MetricsAggregator;
use App\Models\AlertRule;
use App\Models\Notification;
use App\Models\Organization;
use App\Models\Project;
use App\Models\Subscriber;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class SimulateAH101 extends Command
{
    protected $signature = 'simulate:ah-101';
    protected $description = 'Simulate AH-101 using the MetricsAggregator';

    public function handle(MetricsAggregator $aggregator)
    {
        $this->info('Simulating AH-101: Setting up database state...');
        Cache::flush(); // Clear cache for a clean test

        // 1. Setup Organization and Projects
        $organization = Organization::factory()->create(['name' => 'Acme Corp']);
        app()->instance('currentOrganization', $organization);

        $acmePayments = Project::factory()->create(['organization_id' => $organization->id, 'name' => 'Acme Payments']);
        $internalServices = Project::factory()->create(['organization_id' => $organization->id, 'name' => 'Internal Services']);

        // Setup prerequisites
        $subA = Subscriber::factory()->create(['project_id' => $acmePayments->id]);
        $ruleA = AlertRule::factory()->create(['project_id' => $acmePayments->id]);

        $subB = Subscriber::factory()->create(['project_id' => $internalServices->id]);
        $ruleB = AlertRule::factory()->create(['project_id' => $internalServices->id]);

        $today = now()->toDateString();

        // 2. Create 5 alerts for Acme Payments today
        Notification::factory()->count(5)->create([
            'project_id' => $acmePayments->id,
            'subscriber_id' => $subA->id,
            'alert_rule_id' => $ruleA->id,
            'created_at' => now(),
        ]);

        // 3. Create 3 alerts for Internal Services today
        Notification::factory()->count(3)->create([
            'project_id' => $internalServices->id,
            'subscriber_id' => $subB->id,
            'alert_rule_id' => $ruleB->id,
            'created_at' => now(),
        ]);

        $this->info("Created 5 alerts for 'Acme Payments'.");
        $this->info("Created 3 alerts for 'Internal Services'.");
        $this->newLine();

        // 4. Test the Aggregator
        $this->warn('Fetching metrics for Acme Payments using MetricsAggregator...');

        $dashboardCount = $aggregator->getDailyAlertCount($today);

        // Output Results
        $this->table(
            ['Metric', 'Expected (Acme Payments)', 'Actual (Aggregator Output)'],
            [
                ['Daily Alerts', 5, $dashboardCount]
            ]
        );

        $this->newLine();
        if ($dashboardCount === 8) {
            $this->error('Bug Confirmed! The aggregator returned 8 (5 + 3) because it lacks project isolation in its queries and cache keys.');
        }
    }
}
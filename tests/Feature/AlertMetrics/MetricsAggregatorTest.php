<?php

namespace Tests\Feature\AlertMetrics;

use App\AlertMetrics\MetricsAggregator;
use App\Models\AlertRule;
use App\Models\Notification;
use App\Models\Organization;
use App\Models\Project;
use App\Models\Subscriber;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class MetricsAggregatorTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_isolates_daily_alert_counts_by_project(): void
    {
        // Arrange: Clear cache to ensure a pristine test state
        Cache::flush();

        // 1. Setup Organization and bind to container for the global scope
        $organization = Organization::factory()->create();
        app()->instance('currentOrganization', $organization);

        // 2. Setup "Acme Payments" (Project A) and "Internal Services" (Project B)
        $projectA = Project::factory()->create(['organization_id' => $organization->id]);
        $projectB = Project::factory()->create(['organization_id' => $organization->id]);

        // 3. Setup Dependencies for both projects
        $subA = Subscriber::factory()->create(['project_id' => $projectA->id]);
        $ruleA = AlertRule::factory()->create(['project_id' => $projectA->id]);

        $subB = Subscriber::factory()->create(['project_id' => $projectB->id]);
        $ruleB = AlertRule::factory()->create(['project_id' => $projectB->id]);

        $today = now()->toDateString();

        // 4. Create 5 alerts for Project A today
        Notification::factory()->count(5)->create([
            'project_id' => $projectA->id,
            'subscriber_id' => $subA->id,
            'alert_rule_id' => $ruleA->id,
            'created_at' => now(),
        ]);

        // 5. Create 3 alerts for Project B today
        Notification::factory()->count(3)->create([
            'project_id' => $projectB->id,
            'subscriber_id' => $subB->id,
            'alert_rule_id' => $ruleB->id,
            'created_at' => now(),
        ]);

        // Act: Initialize the updated Aggregator
        $aggregator = new MetricsAggregator();

        $projectACount = $aggregator->getDailyAlertCount($projectA->id, $today);
        $projectBCount = $aggregator->getDailyAlertCount($projectB->id, $today);

        // Assert: Verify that the data does not bleed across projects
        $this->assertEquals(5, $projectACount, 'Project A should have exactly 5 alerts.');
        $this->assertEquals(3, $projectBCount, 'Project B should have exactly 3 alerts.');

        // Assert: Verify that the cached values are distinctly stored
        $this->assertEquals(
            5, 
            Cache::get("alert-metrics::{$projectA->id}::{$today}"), 
            'Cache key for Project A is missing or incorrect.'
        );
        $this->assertEquals(
            3, 
            Cache::get("alert-metrics::{$projectB->id}::{$today}"), 
            'Cache key for Project B is missing or incorrect.'
        );
    }
}
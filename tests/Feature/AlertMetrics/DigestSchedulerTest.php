<?php

namespace Tests\Feature\AlertMetrics;

use App\AlertMetrics\DigestScheduler;
use App\AlertMetrics\MetricsAggregator;
use App\AlertMetrics\EngagementScorer;
use App\Models\AlertRule;
use App\Models\Notification;
use App\Models\Organization;
use App\Models\Project;
use App\Models\Subscriber;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class DigestSchedulerTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_schedules_digests_only_for_pending_notifications()
    {
        Queue::fake();
        Event::fake();

        // 1. Set up Organization and Project
        $organization = Organization::factory()->create();
        app()->instance('currentOrganization', $organization);

        $project = Project::factory()->create(['organization_id' => $organization->id]);
        $rule = AlertRule::factory()->create(['project_id' => $project->id]);
        $subscriber = Subscriber::factory()->create(['project_id' => $project->id]);

        $today = now()->toDateString();

        // 2. Create 3 'pending' notifications (Needs to be digested)
        $pendingAlerts = Notification::factory()->count(3)->create([
            'project_id' => $project->id,
            'subscriber_id' => $subscriber->id,
            'alert_rule_id' => $rule->id,
            'status' => 'pending',
            'created_at' => now(),
        ]);

        // 3. Create 2 'sent' notifications (Already delivered, should be ignored)
        Notification::factory()->count(2)->create([
            'project_id' => $project->id,
            'subscriber_id' => $subscriber->id,
            'alert_rule_id' => $rule->id,
            'status' => 'sent',
            'created_at' => now(),
        ]);

        // 4. Act: Run the scheduler
        $scheduler = new DigestScheduler(
            $this->app->make(MetricsAggregator::class),
            $this->app->make(EngagementScorer::class)
        );

        $scheduledCount = $scheduler->scheduleDigests($project->id, $today);

        // 5. Assert: It should only schedule 1 digest for our subscriber
        $this->assertEquals(1, $scheduledCount);

        // Assert: The dispatched event should ONLY contain the 3 'pending' alert IDs
        $expectedPendingIds = $pendingAlerts->pluck('id')->toArray();

        Event::assertDispatched(\App\AlertMetrics\Events\DigestScheduled::class, function ($event) use ($expectedPendingIds) {
            // Sort arrays to ensure exact match regardless of order
            $actualIds = $event->alertIds;
            sort($actualIds);
            sort($expectedPendingIds);

            return $actualIds === $expectedPendingIds;
        });

        // Assert: The ProcessAlertDigest job was pushed to the queue
        Queue::assertPushed(\App\AlertMetrics\ProcessAlertDigest::class);
    }
}
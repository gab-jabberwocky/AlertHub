<?php

namespace Tests\Feature\AlertMetrics;

use App\AlertMetrics\Events\DigestScheduled;
use App\AlertMetrics\MetricsServiceProvider;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class DigestSchedulingPipelineTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Ensure the custom service provider is registered in the test environment.
        // (If it's already in your bootstrap/providers.php, this is just a safe redundancy).
        $this->app->register(MetricsServiceProvider::class);
    }

    public function test_digest_scheduled_event_runs_through_full_pipeline()
    {
        // 1. Arrange: Create the event with a high alert count (25 alerts)
        $alertIds = array_fill(0, 25, 1); 

        $event = new DigestScheduled(
            subscriberId: 1, 
            projectId: 1, 
            date: '2026-05-25', 
            alertIds: $alertIds
        );

        // 2. Act: Dispatch the event through Laravel's Event dispatcher.
        // This will trigger all the listeners registered in MetricsServiceProvider sequentially.
        Event::dispatch($event);

        // 3. Assert: Verify every listener in the chain successfully mutated the event object.

        // Listener 1: GenerateDigestId should have populated this
        $this->assertNotNull(
            $event->referenceId, 
            'GenerateDigestId listener failed to set the referenceId.'
        );

        // Listener 2: CalculateDigestWindow should have set this to 'immediate' (> 20 alerts)
        $this->assertEquals(
            'immediate', 
            $event->scheduledWindow, 
            'CalculateDigestWindow listener failed to calculate the correct window.'
        );

        // Listener 3: AssignDigestPriority should have seen 'immediate' and set 'critical'
        $this->assertEquals(
            'critical', 
            $event->priority, 
            'AssignDigestPriority listener failed to assign priority based on the calculated window.'
        );
    }
}
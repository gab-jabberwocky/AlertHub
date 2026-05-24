<?php

namespace Tests\Feature\AlertMetrics;

use App\AlertMetrics\SubscriberResolver;
use App\Models\Project;
use App\Models\Subscriber;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class SubscriberResolverTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_resolves_existing_subscriber_by_external_id_when_email_is_missing()
    {
        $project = Project::factory()->create();

        // 1. Setup an existing subscriber with NO email, only external_id
        $existingSubscriber = Subscriber::create([
            'project_id' => $project->id,
            'email' => null,
            'external_id' => 'monitor-user-123',
            'name' => 'Monitoring Bot',
            'notification_count' => 0,
        ]);

        $resolver = new SubscriberResolver();

        // 2. Simulate an incoming webhook payload with NO email
        $payload = [
            'external_id' => 'monitor-user-123',
            'name' => 'Monitoring Bot',
            'source' => 'monitoring',
        ];

        // 3. Act
        $resolvedSubscriber = $resolver->resolve($project->id, $payload);

        // 4. Assert it found the existing one and didn't create a duplicate
        $this->assertNotNull($resolvedSubscriber);
        $this->assertEquals($existingSubscriber->id, $resolvedSubscriber->id);
        $this->assertDatabaseCount('subscribers', 1);
    }

    public function test_lock_keys_are_unique_per_external_id_when_email_is_missing()
    {
        $project = Project::factory()->create();
        $resolver = new SubscriberResolver();

        // Spy on the Cache facade to ensure the lock key is built correctly
        Cache::spy();

        $payload = [
            'external_id' => 'unique-contact-999',
            'source' => 'monitoring',
        ];

        $resolver->resolve($project->id, $payload);

        // Assert the lock key uses the external_id, NOT an empty string
        Cache::shouldHaveReceived('lock')
            ->with('subscriber-lock:unique-contact-999', 5)
            ->once();
    }
}
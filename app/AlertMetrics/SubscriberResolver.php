<?php

namespace App\AlertMetrics;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Contracts\Cache\LockTimeoutException;

class SubscriberResolver
{
    /**
     * Resolve a subscriber from the webhook payload.
     *
     * Finds an existing subscriber or creates a new one based on the
     * payload data. Uses cache locking to prevent duplicate creation
     * during concurrent webhook processing.
     *
     * @param  int     $projectId  The project to scope the subscriber to
     * @param  array   $payload    The webhook event payload
     * @return \App\Models\Subscriber|null
     */
    public function resolve(int $projectId, array $payload): ?\App\Models\Subscriber
    {
        $email = $this->extractEmail($payload);
        $externalId = $this->extractExternalId($payload);
        $name = $this->extractName($payload);

        if (!$email && !$externalId) {
            Log::warning('SubscriberResolver: No identifiable information in payload', [
                'project_id' => $projectId,
            ]);
            return null;
        }

        // Try to find existing subscriber
        // FIX 1: Use external_id if email is null so locks don't globally collide.
        $subscriber = $this->findSubscriber($projectId, $email, $externalId);

        if ($subscriber) {
            return $subscriber;
        }

        // Subscriber doesn't exist — create with lock to prevent duplicates.
        $identifier = $email ?: $externalId;
        $lockKey = "subscriber-lock:{$identifier}";

        $lock = Cache::lock($lockKey, 5);

        try {
            // FIX 2: Use block(5) to wait up to 5 seconds for the lock 
            // instead of failing silently when concurrency spikes.
            if ($lock->block(5)) {
                // Double-check after acquiring lock
                $subscriber = $this->findSubscriber($projectId, $email, $externalId);

                if ($subscriber) {
                    return $subscriber;
                }

                $subscriber = \App\Models\Subscriber::create([
                    'project_id' => $projectId,
                    'email' => $email,
                    'external_id' => $externalId,
                    'name' => $name ?? 'Unknown',
                    'notification_count' => 0,
                    'metadata' => json_encode($this->extractMetadata($payload)),
                ]);

                Log::info('SubscriberResolver: Created new subscriber', [
                    'project_id' => $projectId,
                    'subscriber_id' => $subscriber->id,
                    'identifier' => $identifier,
                ]);

                return $subscriber;
            }
        } catch (LockTimeoutException $e) {
            Log::error('SubscriberResolver: Could not acquire lock (timeout) for subscriber creation', [
                'project_id' => $projectId,
                'identifier' => $identifier,
            ]);
        } finally {
            optional($lock)->release();
        }

        return null;
    }

    /**
     * Extract email from various payload structures.
     */
    protected function extractEmail(array $payload): ?string
    {
        // GitHub format
        if (isset($payload['payload']['sender']['email'])) {
            return $payload['payload']['sender']['email'];
        }

        // Stripe format
        if (isset($payload['payload']['customer']['email'])) {
            return $payload['payload']['customer']['email'];
        }

        // Generic format
        if (isset($payload['payload']['contact']['email'])) {
            return $payload['payload']['contact']['email'];
        }

        // Direct email field
        if (isset($payload['email'])) {
            return $payload['email'];
        }

        return null;
    }

    /**
     * Extract external ID from various payload structures.
     */
    protected function extractExternalId(array $payload): ?string
    {
        if (isset($payload['payload']['sender']['login'])) {
            return $payload['payload']['sender']['login'];
        }

        if (isset($payload['payload']['customer']['id'])) {
            return $payload['payload']['customer']['id'];
        }

        if (isset($payload['payload']['contact']['external_id'])) {
            return $payload['payload']['contact']['external_id'];
        }

        if (isset($payload['external_id'])) {
            return $payload['external_id'];
        }

        return null;
    }

    /**
     * Extract display name from payload.
     */
    protected function extractName(array $payload): ?string
    {
        if (isset($payload['payload']['commits'][0]['author']['name'])) {
            return $payload['payload']['commits'][0]['author']['name'];
        }

        if (isset($payload['payload']['contact']['name'])) {
            return $payload['payload']['contact']['name'];
        }

        if (isset($payload['name'])) {
            return $payload['name'];
        }

        return null;
    }

    /**
     * Extract metadata from payload for subscriber record.
     */
    protected function extractMetadata(array $payload): array
    {
        return [
            'source' => $payload['source'] ?? 'unknown',
            'first_seen_event' => $payload['event_type'] ?? null,
            'created_via' => 'webhook',
        ];
    }

    /**
     * Find subscriber from payload for subscriber record.
     */
    protected function findSubscriber(int $projectId, string $email = null, string $externalId = null): ?\App\Models\Subscriber
    {
        return \App\Models\Subscriber::where('project_id', $projectId)
            ->where(function ($query) use ($email, $externalId) {
                if ($email) {
                    $query->orWhere('email', $email);
                }
                if ($externalId) {
                    $query->orWhere('external_id', $externalId);
                }
            })
            ->first();
    }
}
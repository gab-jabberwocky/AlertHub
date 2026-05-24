<?php

namespace App\Jobs;

use App\Models\Notification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Http;

class SendNotification implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    // Give network requests a bit more leeway (5 attempts)
    public int $tries = 5;
    
    // Exponential backoff: 10s, 30s, 2m, 10m
    public array $backoff = [10, 30, 120, 600];

    public function __construct(public Notification $notification)
    {
    }

    /**
     * The unique ID of the job.
     */
    public function uniqueId(): string
    {
        // Guarantee we don't accidentally queue the exact same notification delivery twice
        return (string) $this->notification->id;
    }

    public function handle(): void
    {
        // Using saveQuietly() so we don't trigger the NotificationCreated event again
        $this->notification->status = 'processing';
        $this->notification->saveQuietly();

        try {
            if ($this->notification->channel === 'email') {
                // Example of sending an email:
                // Mail::to($this->notification->subscriber->email)->send(new AlertMail($this->notification));
                Log::info("Simulated: Email sent to {$this->notification->subscriber->email}");
            } elseif ($this->notification->channel === 'webhook') {
                // Example of sending a webhook payload downstream:
                // $url = $this->notification->subscriber->metadata['webhook_url'] ?? '';
                // Http::timeout(10)->post($url, $this->notification->payload);
                Log::info("Simulated: Webhook sent for notification {$this->notification->id}");
            }

            // Mark as sent
            $this->notification->status = 'sent';
            $this->notification->sent_at = now();
            $this->notification->saveQuietly();
            
        } catch (\Exception $e) {
            // Re-throw the exception so Laravel's queue worker knows it failed 
            // and can apply the backoff & retry logic.
            throw $e;
        }
    }

    /**
     * Handle a complete job failure (exhausted all retries).
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('SendNotification job exhausted all retries and failed.', [
            'notification_id' => $this->notification->id,
            'error' => $exception->getMessage(),
        ]);

        $this->notification->status = 'failed';
        $this->notification->saveQuietly();
    }
}
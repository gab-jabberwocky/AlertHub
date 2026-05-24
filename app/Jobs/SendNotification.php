<?php

namespace App\Jobs;

use App\Models\Subscriber;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Http;

class SendNotification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     * Network requests (like emails/webhooks) get 5 attempts.
     */
    public int $tries = 5;
    
    /**
     * Exponential backoff in seconds: 10s, 30s, 2m, 10m
     */
    public array $backoff = [10, 30, 120, 600];

    /**
     * Create a new job instance.
     */
    public function __construct(
        public Subscriber $subscriber,
        public string $type,
        public string $subject,
        public array $body
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            // If this is a digest, format and send the email
            if ($this->type === 'digest') {
                // In a real application, you would pass this data to a Mailable:
                // Mail::to($this->subscriber->email)->send(new \App\Mail\AlertDigestMail($this->subject, $this->body));

                Log::info("Simulated: Digest email sent successfully", [
                    'subscriber_email' => $this->subscriber->email,
                    'subject' => $this->subject,
                    'alert_count' => $this->body['total_alerts'] ?? 0,
                ]);
            } 
            // Handle other notification types (e.g., individual alerts, webhooks)
            else {
                Log::info("Simulated: {$this->type} notification sent", [
                    'subscriber_email' => $this->subscriber->email,
                    'subject' => $this->subject,
                ]);
            }

        } catch (\Exception $e) {
            // Re-throw the exception so Laravel's queue worker knows it failed 
            // and can properly apply the backoff & retry logic.
            throw $e;
        }
    }

    /**
     * Handle a complete job failure (exhausted all retries).
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('SendNotification job exhausted all retries and failed.', [
            'subscriber_id' => $this->subscriber->id,
            'type' => $this->type,
            'subject' => $this->subject,
            'error' => $exception->getMessage(),
        ]);
    }
}
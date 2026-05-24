<?php

namespace App\Jobs;

use App\AlertPipeline\Pipeline;
use App\AlertPipeline\Handlers\DeduplicationHandler;
use App\AlertPipeline\Handlers\ValidationHandler;
use App\AlertPipeline\Handlers\SubscriberMatchHandler;
use App\AlertPipeline\Handlers\RuleEvaluationHandler;
use App\AlertPipeline\Handlers\NotificationDispatchHandler;
use App\Models\Project;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessWebhookEvent implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    // Retry 3 times before failing completely
    public int $tries = 3;
    
    // Backoff in seconds for each retry: 10s, then 30s, then 60s
    public array $backoff = [10, 30, 60];

    public function __construct(
        public array $payload,
        public string $sourceType,
        public string $projectUuid,
        public int $webhookSourceId
    ) {}

    /**
     * The unique ID of the job.
     */
    public function uniqueId(): string
    {
        // Prevent concurrent processing of the exact same event
        $payloadHash = md5(json_encode($this->payload));
        return "{$this->webhookSourceId}:{$payloadHash}";
    }

    public function handle(Pipeline $pipeline): void
    {
        $project = Project::where('uuid', $this->projectUuid)->first();
        if (!$project) return;

        $context = [
            'source_type' => $this->sourceType,
            'project_uuid' => $this->projectUuid,
            'project_id' => $project->id,
            'webhook_source_id' => $this->webhookSourceId,
        ];

        $pipeline
            ->pipe(app(DeduplicationHandler::class))
            ->pipe(app(ValidationHandler::class))
            ->pipe(app(SubscriberMatchHandler::class))
            ->pipe(app(RuleEvaluationHandler::class))
            ->pipe(app(NotificationDispatchHandler::class));

        $pipeline->process($this->payload, $context);
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('ProcessWebhookEvent job failed.', [
            'source_id' => $this->webhookSourceId,
            'error' => $exception->getMessage(),
            'payload' => $this->payload
        ]);
    }
}
<?php

namespace App\AlertPipeline\Handlers;

use App\AlertPipeline\Handler;
use App\AlertPipeline\PipelineState;
use Illuminate\Support\Facades\Log;

class ValidationHandler extends Handler
{
    public function process(array &$payload, array &$context): PipelineState
    {
        // Simple structural validation based on source type
        $isValid = match ($context['source_type']) {
            'github' => isset($payload['event_type'], $payload['payload']['repository']),
            'stripe' => isset($payload['event_type'], $payload['payload']['id']),
            'monitoring' => isset($payload['event_type'], $payload['payload']['alert_id']),
            default => true,
        };

        if (!$isValid) {
            Log::warning("Invalid webhook payload structure for source type: {$context['source_type']}", ['payload' => $payload]);
            return PipelineState::QUIT;
        }

        return PipelineState::CONTINUE;
    }
}
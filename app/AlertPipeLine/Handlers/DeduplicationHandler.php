<?php

namespace App\AlertPipeline\Handlers;

use App\AlertPipeline\Handler;
use App\AlertPipeline\PipelineState;
use Illuminate\Support\Facades\Cache;

class DeduplicationHandler extends Handler
{
    public function process(array &$payload, array &$context): PipelineState
    {
        // Create a unique hash based on payload and source ID
        $hash = md5(json_encode($payload) . $context['webhook_source_id']);
        $cacheKey = "webhook_event_dedup:{$hash}";

        if (Cache::has($cacheKey)) {
            return PipelineState::QUIT;
        }

        // Cache for 5 minutes
        Cache::put($cacheKey, true, now()->addMinutes(5));

        return PipelineState::CONTINUE;
    }
}
<?php

namespace App\AlertPipeline\Handlers;

use App\AlertPipeline\Handler;
use App\AlertPipeline\PipelineState;
use App\AlertMetrics\SubscriberResolver; // Assuming this is the legacy module namespace

class SubscriberMatchHandler extends Handler
{
    public function __construct(protected SubscriberResolver $resolver) {}

    public function process(array &$payload, array &$context): PipelineState
    {
        // Leverage the legacy module to find or create the subscriber
        $subscriber = $this->resolver->resolve($context['project_id'], $payload);

        if (!$subscriber) {
            return PipelineState::QUIT;
        }

        // Attach subscriber to context for the next handlers
        $context['subscriber_id'] = $subscriber->id;

        return PipelineState::CONTINUE;
    }
}
<?php

namespace App\AlertPipeline\Handlers;

use App\AlertPipeline\Handler;
use App\AlertPipeline\PipelineState;
use App\Models\AlertRule;

class RuleEvaluationHandler extends Handler
{
    public function process(array &$payload, array &$context): PipelineState
    {
        $eventType = $payload['event_type'] ?? 'unknown';

        $rules = AlertRule::where('project_id', $context['project_id'])
            ->where('source_type', $context['source_type'])
            ->where('event_type', $eventType)
            ->where('is_active', true)
            ->get();

        if ($rules->isEmpty()) {
            return PipelineState::QUIT;
        }

        // In a complete implementation, iterate through rules to check custom JSON `conditions` against the payload.
        // We'll take the first successfully matched active rule for the event type:
        $matchedRule = $rules->first(); 

        $context['alert_rule_id'] = $matchedRule->id;
        $context['action'] = $matchedRule->action;

        return PipelineState::CONTINUE;
    }
}
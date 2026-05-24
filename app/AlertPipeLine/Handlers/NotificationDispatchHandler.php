<?php

namespace App\AlertPipeline\Handlers;

use App\AlertPipeline\Handler;
use App\AlertPipeline\PipelineState;
use App\Models\Notification;
use App\Jobs\SendNotification;
use Illuminate\Support\Str;

class NotificationDispatchHandler extends Handler
{
    public function process(array &$payload, array &$context): PipelineState
    {
        $notification = Notification::create([
            'uuid' => Str::uuid(),
            'project_id' => $context['project_id'],
            'subscriber_id' => $context['subscriber_id'],
            'alert_rule_id' => $context['alert_rule_id'],
            'channel' => 'email', // Could be dynamic based on rule
            'subject' => "New Alert: {$payload['event_type']}",
            'body' => "Alert triggered by {$context['source_type']}",
            'payload' => $payload,
            'status' => 'pending',
        ]);

        SendNotification::dispatch($notification);

        return PipelineState::CONTINUE;
    }
}
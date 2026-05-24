<?php

namespace App\Listeners;

use App\Events\NotificationCreated;

class UpdateSubscriberStats
{
    public function handle(NotificationCreated $event): void
    {
        $subscriber = $event->notification->subscriber;

        if ($subscriber) {
            $subscriber->increment('notification_count');
            $subscriber->update(['last_notified_at' => now()]);
        }
    }
}
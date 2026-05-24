<?php

namespace App\Listeners;

use App\Events\NotificationCreated;

class CheckEscalation
{
    public function handle(NotificationCreated $event): void
    {
        $notification = $event->notification;
        $subscriber = $notification->subscriber;

        if (!$subscriber) return;

        // Configuration for escalation: e.g., > 5 notifications in the last 15 minutes
        $threshold = 5; 
        $timeWindow = now()->subMinutes(15); 

        // Query the database for the actual volume in the time window
        $recentNotificationsCount = $subscriber->notifications()
            ->where('created_at', '>=', $timeWindow)
            ->count();

        if ($recentNotificationsCount > $threshold) {
            // Use quiet saving so we don't accidentally re-trigger Eloquent 'updated' loops later
            $notification->status = 'escalated';
            $notification->saveQuietly();
        }
    }
}
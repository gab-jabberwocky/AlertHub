<?php

namespace App\AlertMetrics;

use Illuminate\Support\Facades\Cache;

class MetricsAggregator
{
    /**
     * Get the count of alerts for a given date.
     *
     * Used by the dashboard and digest scheduler to track alert volume.
     * Results are cached for 1 hour to reduce database load.
     *
     * @param  string  $date  Date in Y-m-d format
     * @return int
     */
    public function getDailyAlertCount(string $date): int
    {
        $cacheKey = "alert-metrics::{$date}";

        return Cache::remember($cacheKey, 3600, function () use ($date) {
            return \App\Models\Notification::whereDate('created_at', $date)->count();
        });
    }

    /**
     * Get hourly breakdown of alert counts for a date.
     *
     * @param  string  $date  Date in Y-m-d format
     * @return array
     */
    public function getHourlyBreakdown(string $date): array
    {
        $cacheKey = "alert-metrics::hourly::{$date}";

        return Cache::remember($cacheKey, 1800, function () use ($date) {
            return \App\Models\Notification::whereDate('created_at', $date)
                ->selectRaw('HOUR(created_at) as hour, COUNT(*) as count')
                ->groupByRaw('HOUR(created_at)')
                ->pluck('count', 'hour')
                ->toArray();
        });
    }

    /**
     * Record that an alert was processed.
     *
     * Increments the daily counter and invalidates stale cache.
     *
     * @param  int  $notificationId
     * @return void
     */
    public function recordAlert(int $notificationId): void
    {
        $today = now()->toDateString();
        $counterKey = "alert-metrics::counter::{$today}";

        Cache::increment($counterKey);

        // Invalidate the cached count so next read is fresh
        Cache::forget("alert-metrics::{$today}");
        Cache::forget("alert-metrics::hourly::{$today}");
    }

    /**
     * Get alert count for a specific time window.
     *
     * @param  string  $startDate
     * @param  string  $endDate
     * @return int
     */
    public function getAlertCountForWindow(string $startDate, string $endDate): int
    {
        return \App\Models\Notification::whereBetween('created_at', [$startDate, $endDate])->count();
    }
}

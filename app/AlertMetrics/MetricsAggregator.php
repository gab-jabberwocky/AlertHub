<?php

namespace App\AlertMetrics;

use Illuminate\Support\Facades\Cache;
use App\Models\Notification;

class MetricsAggregator
{
    /**
     * Get the count of alerts for a given project and date.
     *
     * Used by the dashboard and digest scheduler to track alert volume.
     * Results are cached for 1 hour to reduce database load.
     *
     * @param  int     $projectId
     * @param  string  $date  Date in Y-m-d format
     * @return int
     */
    public function getDailyAlertCount(int $projectId, string $date): int
    {
        // FIX: Include project ID in the cache key
        $cacheKey = "alert-metrics::{$projectId}::{$date}";

        return Cache::remember($cacheKey, 3600, function () use ($projectId, $date) {
            return Notification::where('project_id', $projectId) // FIX: Filter by project
                ->whereDate('created_at', $date)
                ->count();
        });
    }

    /**
     * Get hourly breakdown of alert counts for a project and date.
     *
     * @param  int     $projectId
     * @param  string  $date  Date in Y-m-d format
     * @return array
     */
    public function getHourlyBreakdown(int $projectId, string $date): array
    {
        $cacheKey = "alert-metrics::hourly::{$projectId}::{$date}";

        return Cache::remember($cacheKey, 1800, function () use ($projectId, $date) {
            return Notification::where('project_id', $projectId)
                ->whereDate('created_at', $date)
                ->selectRaw('HOUR(created_at) as hour, COUNT(*) as count')
                ->groupByRaw('HOUR(created_at)')
                ->pluck('count', 'hour')
                ->toArray();
        });
    }

    /**
     * Record that an alert was processed.
     *
     * @param  int  $projectId
     * @param  int  $notificationId
     * @return void
     */
    public function recordAlert(int $projectId, int $notificationId): void
    {
        $today = now()->toDateString();
        $counterKey = "alert-metrics::counter::{$projectId}::{$today}";
        Cache::increment($counterKey);

        // Invalidate the project-specific cache
        Cache::forget("alert-metrics::{$projectId}::{$today}");
        Cache::forget("alert-metrics::hourly::{$projectId}::{$today}");
    }

    /**
     * Get alert count for a specific time window and project.
     *
     * @param  int     $projectId
     * @param  string  $startDate
     * @param  string  $endDate
     * @return int
     */
    public function getAlertCountForWindow(int $projectId, string $startDate, string $endDate): int
    {
        return Notification::where('project_id', $projectId)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->count();
    }
}
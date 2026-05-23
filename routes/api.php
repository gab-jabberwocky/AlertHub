<?php

use App\Http\Controllers\AlertRuleController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\SubscriberController;
use App\Http\Controllers\WebhookController;
use App\Http\Controllers\WebhookSourceController;
use Illuminate\Support\Facades\Route;

Route::post('webhooks/{project_uuid}/{source_key}', [WebhookController::class, 'handle']);

Route::middleware(['scope.organization'])->group(function () {
    Route::get('projects', [ProjectController::class, 'index']);
    Route::post('projects', [ProjectController::class, 'store']);
    Route::get('projects/{project}', [ProjectController::class, 'show']);
    Route::put('projects/{project}', [ProjectController::class, 'update']);

    Route::get('projects/{project}/subscribers', [SubscriberController::class, 'index']);
    Route::post('projects/{project}/subscribers', [SubscriberController::class, 'store']);

    Route::get('projects/{project}/notifications', [NotificationController::class, 'index']);

    Route::post('projects/{project}/alert-rules', [AlertRuleController::class, 'store']);
    Route::get('projects/{project}/alert-rules', [AlertRuleController::class, 'index']);

    Route::post('projects/{project}/webhook-sources', [WebhookSourceController::class, 'store']);
});
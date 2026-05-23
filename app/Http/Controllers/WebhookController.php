<?php

namespace App\Http\Controllers;

use App\Jobs\ProcessWebhookEvent;
use App\Models\Project;
use App\Models\WebhookSource;
use Illuminate\Http\Request;

class WebhookController extends Controller
{
    public function handle(Request $request, string $projectUuid, string $sourceKey)
    {
        $project = Project::where('uuid', $projectUuid)->first();

        if (!$project) {
            return $this->jsonError('Project not found.', [], 404);
        }

        $source = WebhookSource::where('project_id', $project->id)
            ->where('source_key', $sourceKey)
            ->where('is_active', true)
            ->first();

        if (!$source) {
            return $this->jsonError('Webhook source not found or inactive.', [], 404);
        }

        if ($source->signing_secret) {
            $signature = $request->header('X-Signature')
                ?? $request->header('X-Hub-Signature')
                ?? $request->header('X-Hub-Signature-256');

            if (!$signature) {
                return $this->jsonError(
                    'Missing signature header for signed webhook source.',
                    ['signature' => ['Signed webhook sources require an X-Signature or X-Hub-Signature header.']],
                    400
                );
            }

            $expected = 'sha256=' . hash_hmac('sha256', $request->getContent(), $source->signing_secret);

            if (!hash_equals($expected, $signature)) {
                return $this->jsonError('Webhook signature verification failed.', [], 400);
            }
        }

        ProcessWebhookEvent::dispatch(
            $request->all(),
            $source->source_type,
            $project->uuid,
            $source->id
        );

        return response()->json(['message' => 'Accepted.'], 202);
    }
}

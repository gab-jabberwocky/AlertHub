<?php

namespace App\Http\Controllers;

use App\Http\Resources\WebhookSourceResource;
use App\Models\Project;
use App\Models\WebhookSource;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class WebhookSourceController extends Controller
{
    public function store(Request $request, Project $project)
    {
        $organization = $request->attributes->get('organization');

        if ($error = $this->authorizeProject($project, $organization)) {
            return $error;
        }

        $validated = $request->validate([
            'source_key' => 'required|string|unique:webhook_sources,source_key,NULL,id,project_id,'.$project->id,
            'source_type' => 'required|in:github,stripe,monitoring,custom',
            'name' => 'required|string|max:255',
            'signing_secret' => 'nullable|string',
            'event_mappings' => 'nullable|array',
            'is_active' => 'nullable|boolean',
        ]);

        $source = WebhookSource::create([
            'project_id' => $project->id,
            'is_active' => $validated['is_active'] ?? true,
            ...$validated,
        ]);

        return (new WebhookSourceResource($source))->response()->setStatusCode(201);
    }
}
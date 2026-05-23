<?php

namespace App\Http\Controllers;

use App\Http\Resources\ProjectResource;
use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ProjectController extends Controller
{
    public function index(Request $request)
    {
        $organization = $request->attributes->get('organization');
        $projects = Project::forOrganization($organization)
            ->paginate($request->query('per_page', 15));

        return ProjectResource::collection($projects);
    }

    public function store(Request $request)
    {
        $organization = $request->attributes->get('organization');

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);

        $project = Project::create([
            'organization_id' => $organization->id,
            'uuid' => Str::uuid(),
            ...$validated,
        ]);

        return (new ProjectResource($project))->response()->setStatusCode(201);
    }

    public function show(Request $request, Project $project)
    {
        $organization = $request->attributes->get('organization');

        if ($error = $this->authorizeProject($project, $organization)) {
            return $error;
        }

        $includeMap = [
            'subscribers' => 'subscribers',
            'alert_rules' => 'alertRules',
            'notifications' => 'notifications',
            'webhook_sources' => 'webhookSources',
        ];

        $relations = collect(explode(',', $request->query('includes', '')))
            ->map(fn ($include) => trim($include))
            ->filter()
            ->map(fn ($include) => $includeMap[$include] ?? null)
            ->filter()
            ->values()
            ->all();

        if (!empty($relations)) {
            $project->load($relations);
        }

        return new ProjectResource($project);
    }

    public function update(Request $request, Project $project)
    {
        $organization = $request->attributes->get('organization');

        if ($error = $this->authorizeProject($project, $organization)) {
            return $error;
        }

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
        ]);

        $project->update($validated);

        return new ProjectResource($project);
    }
}
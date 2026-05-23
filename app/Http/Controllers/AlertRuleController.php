<?php

namespace App\Http\Controllers;

use App\Http\Resources\AlertRuleResource;
use App\Models\Project;
use App\Models\AlertRule;
use Illuminate\Http\Request;

class AlertRuleController extends Controller
{
    public function index(Request $request, Project $project)
    {
        $organization = $request->attributes->get('organization');

        if ($error = $this->authorizeProject($project, $organization)) {
            return $error;
        }

        $rules = AlertRule::where('project_id', $project->id)
            ->paginate($request->query('per_page', 15));

        return AlertRuleResource::collection($rules);
    }

    public function store(Request $request, Project $project)
    {
        $organization = $request->attributes->get('organization');

        if ($error = $this->authorizeProject($project, $organization)) {
            return $error;
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'source_type' => 'required|in:github,stripe,monitoring,custom',
            'event_type' => 'required|string',
            'conditions' => 'nullable|array',
            'action' => 'required|in:notify,escalate,digest',
            'priority' => 'required|in:low,medium,high,critical',
            'is_active' => 'nullable|boolean',
        ]);

        $rule = AlertRule::create([
            'project_id' => $project->id,
            'is_active' => $validated['is_active'] ?? true,
            ...$validated,
        ]);

        return (new AlertRuleResource($rule))->response()->setStatusCode(201);
    }
}
<?php

namespace App\Http\Controllers;

use App\Http\Resources\SubscriberResource;
use App\Models\Project;
use App\Models\Subscriber;
use Illuminate\Http\Request;

class SubscriberController extends Controller
{
    public function index(Request $request, Project $project)
    {
        $organization = $request->attributes->get('organization');

        if ($error = $this->authorizeProject($project, $organization)) {
            return $error;
        }

        $subscribers = Subscriber::where('project_id', $project->id)
            ->paginate($request->query('per_page', 15));

        return SubscriberResource::collection($subscribers);
    }

    public function store(Request $request, Project $project)
    {
        $organization = $request->attributes->get('organization');

        if ($error = $this->authorizeProject($project, $organization)) {
            return $error;
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'nullable|email',
            'external_id' => 'nullable|string',
            'metadata' => 'nullable|array',
        ], [
            'email.email' => 'Email is invalid.',
        ]);

        if (empty($validated['email']) && empty($validated['external_id'])) {
            return $this->jsonError(
                'The given data was invalid.',
                ['subscriber' => ['Either email or external_id is required.']],
                422
            );
        }

        $subscriber = Subscriber::create([
            'project_id' => $project->id,
            ...$validated,
        ]);

        return (new SubscriberResource($subscriber))->response()->setStatusCode(201);
    }
}
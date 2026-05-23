<?php

namespace App\Http\Controllers;

use App\Http\Resources\NotificationResource;
use App\Models\Project;
use App\Models\Notification;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index(Request $request, Project $project)
    {
        $organization = $request->attributes->get('organization');

        if ($error = $this->authorizeProject($project, $organization)) {
            return $error;
        }

        $query = Notification::where('project_id', $project->id);

        if ($request->has('status')) {
            $query->where('status', $request->query('status'));
        }

        if ($request->has('channel')) {
            $query->where('channel', $request->query('channel'));
        }

        if ($request->has('subscriber_id')) {
            $query->where('subscriber_id', $request->query('subscriber_id'));
        }

        if ($request->has('alert_rule_id')) {
            $query->where('alert_rule_id', $request->query('alert_rule_id'));
        }

        $notifications = $query->paginate($request->query('per_page', 15));

        return NotificationResource::collection($notifications);
    }
}
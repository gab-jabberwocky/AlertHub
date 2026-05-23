<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;

abstract class Controller extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

    protected function jsonError(string $message, array $errors = [], int $status = 422)
    {
        $payload = ['message' => $message];

        if (!empty($errors)) {
            $payload['errors'] = $errors;
        }

        return response()->json($payload, $status);
    }

    protected function authorizeProject(
        $project,
        $organization,
        string $message = 'This project does not belong to your organization.'
    ) {
        if ($project->organization_id !== $organization->id) {
            return $this->jsonError($message, ['project' => [$message]], 403);
        }

        return null;
    }
}

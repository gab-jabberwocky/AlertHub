<?php

namespace App\Http\Middleware;

use App\Models\Organization;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateOrganization
{
    public function handle(Request $request, Closure $next): Response
    {
        $authorization = $request->header('Authorization', '');
        $token = str($authorization)->after('Bearer ')->trim();

        if ($token->isEmpty()) {
            return response()->json([
                'message' => 'Unauthenticated.',
                'errors' => ['authorization' => ['Authorization Bearer token is required.']],
            ], 401);
        }

        $organization = Organization::where('api_token', $token)->first();

        if (!$organization) {
            return response()->json([
                'message' => 'Unauthenticated.',
                'errors' => ['authorization' => ['Invalid organization API token.']],
            ], 401);
        }

        app()->instance('currentOrganization', $organization);
        $request->attributes->set('organization', $organization);

        return $next($request);
    }
}
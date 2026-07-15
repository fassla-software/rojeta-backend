<?php

namespace App\Http\Middleware;

use App\Http\Responses\ApiResponse;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (!$user) {
            return ApiResponse::error('UNAUTHORIZED', 'Invalid or expired token', 401);
        }

        if (!in_array($user->role, $roles, true)) {
            return ApiResponse::error('FORBIDDEN', 'You do not have permission to access this resource', 403);
        }

        return $next($request);
    }
}

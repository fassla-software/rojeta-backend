<?php

use App\Http\Middleware\EnsureRole;
use App\Http\Responses\ApiResponse;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'role' => EnsureRole::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (ValidationException $e, Request $request) {
            if (!$request->is('api/*')) {
                return null;
            }

            $details = collect($e->errors())->flatMap(function ($messages, $field) {
                return collect($messages)->map(fn ($message) => [
                    'field' => $field,
                    'message' => $message,
                ]);
            })->values()->all();

            return ApiResponse::error('VALIDATION_ERROR', 'Invalid request body', 400, $details);
        });

        $exceptions->render(function (AuthenticationException $e, Request $request) {
            if (!$request->is('api/*')) {
                return null;
            }

            return ApiResponse::error('UNAUTHORIZED', 'Invalid or expired token', 401);
        });

        $exceptions->render(function (AuthorizationException $e, Request $request) {
            if (!$request->is('api/*')) {
                return null;
            }

            return ApiResponse::error('FORBIDDEN', 'You do not have permission to access this resource', 403);
        });

        $exceptions->render(function (ModelNotFoundException|NotFoundHttpException $e, Request $request) {
            if (!$request->is('api/*')) {
                return null;
            }

            return ApiResponse::error('NOT_FOUND', 'Resource not found', 404);
        });

        $exceptions->render(function (\Throwable $e, Request $request) {
            if (!$request->is('api/*') || config('app.debug')) {
                return null;
            }

            return ApiResponse::error('INTERNAL_ERROR', 'An unexpected error occurred', 500);
        });
    })->create();

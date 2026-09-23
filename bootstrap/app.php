<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        api: __DIR__.'/../routes/api.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'role' => \Spatie\Permission\Middleware\RoleMiddleware::class,
            'permission' => \Spatie\Permission\Middleware\PermissionMiddleware::class,
            'role_or_permission' => \Spatie\Permission\Middleware\RoleOrPermissionMiddleware::class,

            // API middleware
            'api.key' => \App\Http\Middleware\Api\AuthenticateApiKey::class,
            'api.scopes' => \App\Http\Middleware\Api\EnforceApiScopes::class,
            'api.ratelimit' => \App\Http\Middleware\Api\EnforceRateLimit::class,
            'api.idempotent' => \App\Http\Middleware\Api\HandleIdempotency::class,
            'api.log' => \App\Http\Middleware\Api\LogApiRequest::class,

        ]);

        $middleware->redirectGuestsTo(fn () => route('login.post'));
        $middleware->redirectUsersTo(fn () => route('admin.dashboard'));
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->render(function (\Throwable $e, $request) {
            if (!$request->is('api/*')) return null;

            $status = match (true) {
                $e instanceof \Illuminate\Validation\ValidationException => 422,
                $e instanceof \Illuminate\Auth\AuthenticationException => 401,
                $e instanceof \Illuminate\Auth\Access\AuthorizationException => 403,
                $e instanceof \Illuminate\Database\Eloquent\ModelNotFoundException => 404,
                $e instanceof \Symfony\Component\HttpKernel\Exception\NotFoundHttpException => 404,
                $e instanceof \Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException => 405,
                default => 500,
            };

            $type = match (true) {
                $status === 422 => 'validation_error',
                $status === 401 => 'authentication_error',
                $status === 403 => 'permission_error',
                $status === 404 => 'not_found_error',
                $status === 429 => 'rate_limit_error',
                $status >= 500 => 'api_error',
                default => 'invalid_request_error',
            };

            $body = [
                'error' => [
                    'type' => $type,
                    'message' => $status >= 500 && app()->environment('production')
                        ? 'An unexpected error occurred.'
                        : $e->getMessage(),
                    'code' => class_basename($e),
                ],
            ];

            if ($e instanceof \Illuminate\Validation\ValidationException) {
                $body['error']['errors'] = $e->errors();
            }

            $requestId = $request->attributes->get('request_id');
            if ($requestId) $body['request_id'] = $requestId;

            return response()->json($body, $status);
        });
    })->create();
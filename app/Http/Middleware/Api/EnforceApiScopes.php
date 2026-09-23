<?php

namespace App\Http\Middleware\Api;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnforceApiScopes
{
    /**
     * Usage in routes: ->middleware('api.scopes:payments:write,payments:read')
     */
    public function handle(Request $request, Closure $next, string ...$required): Response
    {
        $key = $request->attributes->get('api_key');

        if (!$key) {
            return $this->forbidden('API key not resolved.');
        }

        // Publishable keys can only read
        if ($key->type === 'publishable') {
            foreach ($required as $scope) {
                if (str_ends_with($scope, ':write')) {
                    return $this->forbidden('Publishable keys cannot perform write operations.');
                }
            }
        }

        // Secret keys bypass scope checks
        if ($key->type === 'secret') {
            return $next($request);
        }

        // Restricted keys must match at least one of the required scopes
        $granted = $key->scopes ?? [];
        if (in_array('*', $granted, true)) return $next($request);

        foreach ($required as $scope) {
            if (in_array($scope, $granted, true)) {
                return $next($request);
            }
        }

        return $this->forbidden('Missing required scope: ' . implode(' | ', $required));
    }

    protected function forbidden(string $message): Response
    {
        return response()->json([
            'error' => [
                'type' => 'permission_error',
                'message' => $message,
                'code' => 'insufficient_scope',
            ],
        ], 403);
    }
}
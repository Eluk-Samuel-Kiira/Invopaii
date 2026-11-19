<?php

namespace App\Http\Middleware\Api;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Symfony\Component\HttpFoundation\Response;

class EnforceRateLimit
{
    public function handle(Request $request, Closure $next): Response
    {
        $key = $request->attributes->get('api_key');

        if (!$key) return $next($request);

        $limit = $key->rate_limit_per_minute ?? 600;
        $bucket = 'api:' . $key->id;

        if (RateLimiter::tooManyAttempts($bucket, $limit)) {
            $retryAfter = RateLimiter::availableIn($bucket);

            return response()->json([
                'error' => [
                    'type' => 'rate_limit_error',
                    'message' => 'Too many requests.',
                    'code' => 'rate_limit_exceeded',
                ],
            ], 429, [
                'Retry-After' => $retryAfter,
                'X-RateLimit-Limit' => $limit,
                'X-RateLimit-Remaining' => 0,
            ]);
        }

        RateLimiter::hit($bucket, 60);

        $response = $next($request);

        $remaining = RateLimiter::remaining($bucket, $limit);
        $response->headers->set('X-RateLimit-Limit', (string) $limit);
        $response->headers->set('X-RateLimit-Remaining', (string) $remaining);

        return $response;
    }
}
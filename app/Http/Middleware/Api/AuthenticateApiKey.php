<?php

namespace App\Http\Middleware\Api;

use App\Models\Company\ApiKey;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateApiKey
{
    public function handle(Request $request, Closure $next): Response
    {
        $bearer = $request->bearerToken();

        if (!$bearer) {
            return $this->unauthorized('Missing Authorization header. Use: Authorization: Bearer sk_test_...');
        }

        $key = ApiKey::findByPlaintext($bearer);

        if (!$key) {
            return $this->unauthorized('Invalid API key.');
        }

        if (!$key->is_active) {
            return $this->unauthorized("API key is {$key->status}.");
        }

        if ($key->allowed_ips && !$this->ipAllowed($request->ip(), $key->allowed_ips)) {
            return $this->unauthorized('IP address not allowed for this key.');
        }

        // Update last used
        $key->forceFill([
            'last_used_at' => now(),
            'last_used_ip' => $request->ip(),
        ])->save();

        // Bind to the request for downstream middleware/controllers
        $request->attributes->set('api_key', $key);
        $request->attributes->set('company_id', $key->company_id);
        $request->attributes->set('mode', $key->mode);

        return $next($request);
    }

    protected function unauthorized(string $message): Response
    {
        return response()->json([
            'error' => [
                'type' => 'authentication_error',
                'message' => $message,
                'code' => 'invalid_api_key',
            ],
        ], 401);
    }

    protected function ipAllowed(string $ip, array $allowed): bool
    {
        foreach ($allowed as $range) {
            if (str_contains($range, '/')) {
                if ($this->cidrMatch($ip, $range)) return true;
            } elseif ($ip === $range) {
                return true;
            }
        }
        return false;
    }

    protected function cidrMatch(string $ip, string $cidr): bool
    {
        [$subnet, $mask] = explode('/', $cidr);
        return (ip2long($ip) & ~((1 << (32 - (int) $mask)) - 1)) === ip2long($subnet);
    }
}
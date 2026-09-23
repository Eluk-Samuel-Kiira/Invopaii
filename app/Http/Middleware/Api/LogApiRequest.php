<?php

namespace App\Http\Middleware\Api;

use App\Models\Payment\ApiRequestLog;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class LogApiRequest
{
    public function handle(Request $request, Closure $next): Response
    {
        $requestId = $request->header('X-Request-Id') ?? (string) Str::uuid();
        $request->attributes->set('request_id', $requestId);

        $startedAt = microtime(true);

        $response = $next($request);

        $durationMs = (int) round((microtime(true) - $startedAt) * 1000);

        // Only log to DB if there's a company context (i.e. auth passed)
        $companyId = $request->attributes->get('company_id');
        if (!$companyId) {
            return $response->header('X-Request-Id', $requestId);
        }

        try {
            ApiRequestLog::create([
                'company_id' => $companyId,
                'api_key_id' => $request->attributes->get('api_key')?->id,
                'mode' => $request->attributes->get('mode'),
                'method' => $request->method(),
                'path' => $request->path(),
                'route_name' => optional($request->route())->getName(),
                'api_version' => 'v1',
                'status_code' => $response->getStatusCode(),
                'duration_ms' => $durationMs,
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'idempotency_key' => $request->header('Idempotency-Key'),
                'request_id' => $requestId,
                'request_headers' => $this->redactHeaders($request->headers->all()),
                'request_body' => $this->redactBody($request->all()),
                'response_body' => $this->safeJson($response->getContent()),
                'error_code' => $this->extractError($response, 'code'),
                'error_message' => $this->extractError($response, 'message'),
                'created_at' => now(),
            ]);
        } catch (\Throwable $e) {
            // Never let logging break the request
        }

        return $response->header('X-Request-Id', $requestId);
    }

    protected function redactHeaders(array $headers): array
    {
        $redact = ['authorization', 'cookie', 'x-api-key'];
        foreach ($redact as $h) {
            if (isset($headers[$h])) $headers[$h] = ['[redacted]'];
        }
        return $headers;
    }

    protected function redactBody(array $body): array
    {
        // Redact card fields if present
        $sensitive = ['card_number', 'cvv', 'cvc', 'pin', 'password', 'secret'];
        foreach ($sensitive as $field) {
            if (isset($body[$field])) $body[$field] = '[redacted]';
        }
        return $body;
    }

    protected function safeJson(string $content): ?array
    {
        $decoded = json_decode($content, true);
        return is_array($decoded) ? $decoded : null;
    }

    protected function extractError(Response $response, string $key): ?string
    {
        if ($response->getStatusCode() < 400) return null;
        $body = $this->safeJson($response->getContent());
        return $body['error'][$key] ?? null;
    }
}
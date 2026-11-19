<?php

namespace App\Http\Middleware\Api;

use App\Models\Company\IdempotencyKey;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class HandleIdempotency
{
    public function handle(Request $request, Closure $next): Response
    {
        // Only POST/PUT/DELETE need idempotency
        if (!in_array($request->method(), ['POST', 'PUT', 'PATCH', 'DELETE'])) {
            return $next($request);
        }

        $key = $request->header('Idempotency-Key');

        if (!$key) {
            // Optional but recommended — allow requests without it
            return $next($request);
        }

        $companyId = $request->attributes->get('company_id');
        $mode = $request->attributes->get('mode');
        $requestHash = hash('sha256', $request->getContent());

        $record = IdempotencyKey::where('company_id', $companyId)
            ->where('mode', $mode)
            ->where('key', $key)
            ->first();

        // Already completed → return cached response
        if ($record && $record->status === 'completed') {
            if ($record->request_hash !== $requestHash) {
                return response()->json([
                    'error' => [
                        'type' => 'idempotency_error',
                        'message' => 'This Idempotency-Key was used with a different request body.',
                        'code' => 'idempotency_body_mismatch',
                    ],
                ], 422);
            }

            return response()->json(
                $record->response_body,
                $record->response_code ?? 200
            )->header('Idempotency-Replayed', 'true');
        }

        // In progress → reject concurrent duplicate
        if ($record && $record->status === 'in_progress') {
            return response()->json([
                'error' => [
                    'type' => 'idempotency_error',
                    'message' => 'A request with this Idempotency-Key is currently in progress.',
                    'code' => 'idempotency_in_progress',
                ],
            ], 409);
        }

        // Fresh — record it
        $record = IdempotencyKey::create([
            'company_id' => $companyId,
            'mode' => $mode,
            'key' => $key,
            'method' => $request->method(),
            'endpoint' => $request->path(),
            'request_hash' => $requestHash,
            'status' => 'in_progress',
            'locked_at' => now(),
            'expires_at' => now()->addHours(24),
        ]);

        try {
            $response = $next($request);

            // Record the successful response
            $record->update([
                'status' => $response->getStatusCode() < 500 ? 'completed' : 'failed',
                'response_code' => $response->getStatusCode(),
                'response_body' => json_decode($response->getContent(), true),
            ]);

            return $response->header('Idempotency-Recorded', 'true');
        } catch (\Throwable $e) {
            $record->update(['status' => 'failed']);
            throw $e;
        }
    }
}
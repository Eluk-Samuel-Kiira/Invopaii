<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\PaymentApiController;
use App\Http\Controllers\Api\V1\RefundApiController;
use App\Http\Controllers\Api\V1\CustomerApiController;
use App\Http\Controllers\Api\V1\PayoutApiController;

/*
|--------------------------------------------------------------------------
| API v1 — Merchant endpoints
|--------------------------------------------------------------------------
| Every route here is authenticated via an API key. The key must be a
| "secret" or "restricted" type (not publishable).
*/

Route::prefix('v1')
    ->middleware(['api.key', 'api.ratelimit', 'api.log', 'api.idempotent'])
    ->group(function () {

        // ─── Payments ───
        Route::prefix('payments')
            ->middleware('api.scopes:payments:write')
            ->group(function () {
                Route::post('/', [PaymentApiController::class, 'store'])->name('api.payments.store');
                Route::post('/{id}/capture', [PaymentApiController::class, 'capture'])->name('api.payments.capture');
                Route::post('/{id}/cancel', [PaymentApiController::class, 'cancel'])->name('api.payments.cancel');
            });

        Route::prefix('payments')
            ->middleware('api.scopes:payments:read')
            ->group(function () {
                Route::get('/', [PaymentApiController::class, 'index'])->name('api.payments.index');
                Route::get('/{id}', [PaymentApiController::class, 'show'])->name('api.payments.show');
                Route::get('/{id}/attempts', [PaymentApiController::class, 'attempts'])->name('api.payments.attempts');
            });

        // ─── Refunds ───
        Route::prefix('refunds')
            ->middleware('api.scopes:refunds:write')
            ->group(function () {
                Route::post('/', [RefundApiController::class, 'store'])->name('api.refunds.store');
            });

        Route::prefix('refunds')
            ->middleware('api.scopes:refunds:read')
            ->group(function () {
                Route::get('/', [RefundApiController::class, 'index'])->name('api.refunds.index');
                Route::get('/{id}', [RefundApiController::class, 'show'])->name('api.refunds.show');
            });

        // ─── Customers ───
        Route::prefix('customers')
            ->middleware('api.scopes:customers:write')
            ->group(function () {
                Route::post('/', [CustomerApiController::class, 'store'])->name('api.customers.store');
                Route::put('/{id}', [CustomerApiController::class, 'update'])->name('api.customers.update');
            });

        Route::prefix('customers')
            ->middleware('api.scopes:customers:read')
            ->group(function () {
                Route::get('/', [CustomerApiController::class, 'index'])->name('api.customers.index');
                Route::get('/{id}', [CustomerApiController::class, 'show'])->name('api.customers.show');
            });

        // ─── Payouts ───
        Route::prefix('payouts')
            ->middleware('api.scopes:payouts:read')
            ->group(function () {
                Route::get('/', [PayoutApiController::class, 'index'])->name('api.payouts.index');
                Route::get('/{id}', [PayoutApiController::class, 'show'])->name('api.payouts.show');
            });

        // ─── Balance ───
        Route::get('/balance', [PayoutApiController::class, 'balance'])
            ->middleware('api.scopes:balance:read')
            ->name('api.balance.show');
    });
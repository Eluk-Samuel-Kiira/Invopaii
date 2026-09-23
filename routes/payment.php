<?php

use Illuminate\Support\Facades\Route;


Route::post('/webhooks/{providerCode}', function (Request $request, $providerCode) {
    $event = \App\Services\Payment\WebhookReceiver::receive($providerCode, $request);
    return response()->json(['received' => true, 'id' => $event?->uuid]);
})->withoutMiddleware(['csrf']);


use App\Http\Controllers\Webhooks\ProviderWebhookController;

/*
|--------------------------------------------------------------------------
| Public — Provider Webhooks
|--------------------------------------------------------------------------
| No auth. No CSRF. Signature verification happens in the controller.
*/

Route::post('/webhooks/{providerCode}', [ProviderWebhookController::class, 'receive'])
    ->withoutMiddleware(['csrf', 'auth', 'verified'])
    ->name('webhooks.provider');

// Legacy callback URL used by some providers (Pesapal, M-PESA)
Route::get('/webhooks/{providerCode}/callback', [ProviderWebhookController::class, 'callback'])
    ->withoutMiddleware(['csrf', 'auth', 'verified'])
    ->name('webhooks.provider.callback');


use App\Http\Controllers\Payment\PaymentController;




/*
|--------------------------------------------------------------------------
| Admin — Payments
|--------------------------------------------------------------------------
*/

Route::prefix('admin/payments')
    ->middleware(['auth', 'permission:view payments'])
    ->name('admin.payments.')
    ->group(function () {
        Route::get('/',        [PaymentController::class, 'index'])->name('index');
        Route::get('/data',    [PaymentController::class, 'getPayments'])->name('data');
        Route::get('/stats',   [PaymentController::class, 'getStats'])->name('stats');
        Route::get('/form-options', [PaymentController::class, 'getFormOptions'])->name('form-options');
        Route::get('/{id}',    [PaymentController::class, 'getPayment'])->whereNumber('id')->name('show');
        Route::get('/{id}/attempts', [PaymentController::class, 'getAttempts'])->whereNumber('id')->name('attempts');
    });

Route::prefix('admin/payments')
    ->middleware(['auth', 'permission:edit payments'])
    ->group(function () {
        Route::post('/{id}/retry',  [PaymentController::class, 'retryPayment'])->whereNumber('id')->name('admin.payments.retry');
        Route::post('/{id}/cancel', [PaymentController::class, 'cancelPayment'])->whereNumber('id')->name('admin.payments.cancel');
    });

Route::prefix('admin/webhook-events')
    ->middleware(['auth', 'permission:view webhook events'])
    ->name('admin.webhook-events.')
    ->group(function () {
        Route::get('/',     [PaymentController::class, 'webhookEventsIndex'])->name('index');
        Route::get('/data', [PaymentController::class, 'getWebhookEvents'])->name('data');
        Route::get('/{id}', [PaymentController::class, 'getWebhookEvent'])->whereNumber('id')->name('show');
        Route::post('/{id}/reprocess', [PaymentController::class, 'reprocessWebhookEvent'])->whereNumber('id')->name('reprocess');
    });



use App\Http\Controllers\Platform\FeeScheduleController;
use App\Http\Controllers\Platform\AppliedFeeController;

/*
|--------------------------------------------------------------------------
| Admin — Fee Schedules
|--------------------------------------------------------------------------
*/
Route::prefix('admin/fee-schedules')
    ->middleware(['auth', 'permission:view fee schedules'])
    ->name('admin.fee-schedules.')
    ->group(function () {
        Route::get('/',      [FeeScheduleController::class, 'index'])->name('index');
        Route::get('/data',  [FeeScheduleController::class, 'getFeeSchedules'])->name('data');
        Route::get('/stats', [FeeScheduleController::class, 'getStats'])->name('stats');
        Route::get('/{id}',  [FeeScheduleController::class, 'getFeeSchedule'])->whereNumber('id')->name('get');
    });

Route::prefix('admin/fee-schedules')
    ->middleware(['auth', 'permission:create fee schedules'])
    ->group(function () {
        Route::post('/', [FeeScheduleController::class, 'storeFeeSchedule'])->name('admin.fee-schedules.store');
    });

Route::prefix('admin/fee-schedules')
    ->middleware(['auth', 'permission:edit fee schedules'])
    ->group(function () {
        Route::put('/{id}',              [FeeScheduleController::class, 'updateFeeSchedule'])->whereNumber('id')->name('admin.fee-schedules.update');
        Route::patch('/{id}/toggle',     [FeeScheduleController::class, 'toggleFeeSchedule'])->whereNumber('id')->name('admin.fee-schedules.toggle');
        Route::patch('/{id}/set-default',[FeeScheduleController::class, 'setDefault'])->whereNumber('id')->name('admin.fee-schedules.set-default');
    });

Route::prefix('admin/fee-schedules')
    ->middleware(['auth', 'permission:delete fee schedules'])
    ->group(function () {
        Route::delete('/{id}', [FeeScheduleController::class, 'deleteFeeSchedule'])->whereNumber('id')->name('admin.fee-schedules.delete');
    });

/*
|--------------------------------------------------------------------------
| Admin — Applied Fees (read-only audit)
|--------------------------------------------------------------------------
*/
Route::prefix('admin/applied-fees')
    ->middleware(['auth', 'permission:view fee schedules'])
    ->name('admin.applied-fees.')
    ->group(function () {
        Route::get('/',      [AppliedFeeController::class, 'index'])->name('index');
        Route::get('/data',  [AppliedFeeController::class, 'getAppliedFees'])->name('data');
        Route::get('/stats', [AppliedFeeController::class, 'getStats'])->name('stats');
        Route::get('/form-options', [AppliedFeeController::class, 'getFormOptions'])->name('form-options');
        Route::get('/{id}',  [AppliedFeeController::class, 'getAppliedFee'])->whereNumber('id')->name('get');
    });
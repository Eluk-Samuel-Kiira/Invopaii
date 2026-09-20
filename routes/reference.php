<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Reference\CountryController;
use App\Http\Controllers\Reference\CurrencyController;
use App\Http\Controllers\Reference\ExchangeRateController;

Route::middleware(['auth', 'role:super_admin'])
    ->prefix('admin')
    ->group(function () {

        // ─── Countries ───
        Route::prefix('countries')->name('countries.')->group(function () {
            Route::get('/',        [CountryController::class, 'index'])->name('index');
            Route::get('/data',    [CountryController::class, 'getCountries'])->name('data');
            Route::get('/{id}',    [CountryController::class, 'getCountry'])->whereNumber('id')->name('get');
            Route::post('/',       [CountryController::class, 'storeCountry'])->name('store');
            Route::put('/{id}',    [CountryController::class, 'updateCountry'])->whereNumber('id')->name('update');
            Route::delete('/{id}', [CountryController::class, 'deleteCountry'])->whereNumber('id')->name('delete');
            Route::patch('/{id}/toggle-flag', [CountryController::class, 'toggleFlag'])->whereNumber('id')->name('toggle-flag');
        });

        // ─── Currencies ───
        Route::prefix('currencies')->name('currencies.')->group(function () {
            Route::get('/',        [CurrencyController::class, 'index'])->name('index');
            Route::get('/data',    [CurrencyController::class, 'getCurrencies'])->name('data');
            Route::get('/{id}',    [CurrencyController::class, 'getCurrency'])->whereNumber('id')->name('get');
            Route::post('/',       [CurrencyController::class, 'storeCurrency'])->name('store');
            Route::put('/{id}',    [CurrencyController::class, 'updateCurrency'])->whereNumber('id')->name('update');
            Route::delete('/{id}', [CurrencyController::class, 'deleteCurrency'])->whereNumber('id')->name('delete');
            Route::patch('/{id}/toggle-flag', [CurrencyController::class, 'toggleFlag'])->whereNumber('id')->name('toggle-flag');
        });

        // ─── Exchange Rates ───
        Route::prefix('exchange-rates')->name('exchange-rates.')->group(function () {
            Route::get('/',        [ExchangeRateController::class, 'index'])->name('index');
            Route::get('/data',    [ExchangeRateController::class, 'getExchangeRates'])->name('data');
            Route::get('/current', [ExchangeRateController::class, 'getCurrentRate'])->name('current');
            Route::get('/currency-codes', [ExchangeRateController::class, 'getCurrencyCodes'])->name('currency-codes');
            Route::get('/{id}',    [ExchangeRateController::class, 'getExchangeRate'])->whereNumber('id')->name('get');
            Route::post('/',       [ExchangeRateController::class, 'storeExchangeRate'])->name('store');
            Route::put('/{id}',    [ExchangeRateController::class, 'updateExchangeRate'])->whereNumber('id')->name('update');
            Route::delete('/{id}', [ExchangeRateController::class, 'deleteExchangeRate'])->whereNumber('id')->name('delete');
            Route::patch('/{id}/close', [ExchangeRateController::class, 'closeRate'])->whereNumber('id')->name('close');
        });
    });

    

    
use App\Http\Controllers\Company\CompanyController;

/*
|--------------------------------------------------------------------------
| Admin — Companies
|--------------------------------------------------------------------------
| Every route is gated by an explicit permission. No role checks.
| Role → permission mapping lives in RolesSeeder.
*/

// ─── Read ───
Route::prefix('admin')
    ->middleware(['auth', 'permission:view companies'])
    ->group(function () {
        Route::get('/companies',              [CompanyController::class, 'index'])->name('admin.companies.index');
        Route::get('/companies/data',         [CompanyController::class, 'getCompanies'])->name('admin.companies.data');
        Route::get('/companies/form-options', [CompanyController::class, 'getFormOptions'])->name('admin.companies.form-options');
        Route::get('/companies/{id}',         [CompanyController::class, 'getCompany'])->whereNumber('id')->name('admin.companies.get');
    });

// ─── Create ───
Route::prefix('admin')
    ->middleware(['auth', 'permission:create companies'])
    ->group(function () {
        Route::post('/companies', [CompanyController::class, 'storeCompany'])->name('admin.companies.store');
    });

// ─── Update ───
Route::prefix('admin')
    ->middleware(['auth', 'permission:edit companies'])
    ->group(function () {
        Route::put('/companies/{id}', [CompanyController::class, 'updateCompany'])->whereNumber('id')->name('admin.companies.update');
    });

// ─── Delete ───
Route::prefix('admin')
    ->middleware(['auth', 'permission:delete companies'])
    ->group(function () {
        Route::delete('/companies/{id}', [CompanyController::class, 'deleteCompany'])->whereNumber('id')->name('admin.companies.delete');
    });

// ─── Approval actions (status change + flag toggle) ───
Route::prefix('admin')
    ->middleware(['auth', 'permission:approve companies'])
    ->group(function () {
        Route::patch('/companies/{id}/change-status', [CompanyController::class, 'changeStatus'])->whereNumber('id')->name('admin.companies.change-status');
        Route::patch('/companies/{id}/toggle-flag',   [CompanyController::class, 'toggleFlag'])->whereNumber('id')->name('admin.companies.toggle-flag');
    });
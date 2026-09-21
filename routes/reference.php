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


use App\Http\Controllers\Company\ComplianceController;

/*
|--------------------------------------------------------------------------
| Admin — Company Compliance
|--------------------------------------------------------------------------
*/

// Read
Route::prefix('admin/companies/{companyId}')
    ->middleware(['auth', 'permission:view company compliance'])
    ->whereNumber('companyId')
    ->group(function () {
        Route::get('/representatives',   [ComplianceController::class, 'getRepresentatives'])->name('admin.companies.representatives.index');
        Route::get('/documents',         [ComplianceController::class, 'getDocuments'])->name('admin.companies.documents.index');
        Route::get('/checks',            [ComplianceController::class, 'getVerificationChecks'])->name('admin.companies.checks.index');
    });

// Representative write
Route::prefix('admin/companies/{companyId}')
    ->middleware(['auth', 'permission:manage company documents'])
    ->whereNumber('companyId')
    ->group(function () {
        Route::post('/representatives',           [ComplianceController::class, 'storeRepresentative'])->name('admin.companies.representatives.store');
    });

Route::prefix('admin')
    ->middleware(['auth', 'permission:manage company documents'])
    ->group(function () {
        Route::put('/representatives/{id}',    [ComplianceController::class, 'updateRepresentative'])->whereNumber('id')->name('admin.representatives.update');
        Route::delete('/representatives/{id}', [ComplianceController::class, 'deleteRepresentative'])->whereNumber('id')->name('admin.representatives.delete');
    });

// Document write + upload
Route::prefix('admin/companies/{companyId}')
    ->middleware(['auth', 'permission:manage company documents'])
    ->whereNumber('companyId')
    ->group(function () {
        Route::post('/documents', [ComplianceController::class, 'uploadDocument'])->name('admin.companies.documents.store');
    });

Route::prefix('admin')
    ->middleware(['auth', 'permission:manage company documents'])
    ->group(function () {
        Route::delete('/documents/{id}', [ComplianceController::class, 'deleteDocument'])->whereNumber('id')->name('admin.documents.delete');
    });

// Document view — download/preview signed URL (read permission)
Route::prefix('admin')
    ->middleware(['auth', 'permission:view company documents'])
    ->group(function () {
        Route::get('/documents/{id}/view', [ComplianceController::class, 'viewDocument'])->whereNumber('id')->name('admin.documents.view');
    });

// Document review — approval action
Route::prefix('admin')
    ->middleware(['auth', 'permission:manage company documents'])
    ->group(function () {
        Route::patch('/documents/{id}/review', [ComplianceController::class, 'reviewDocument'])->whereNumber('id')->name('admin.documents.review');
    });

// Run verification check
Route::prefix('admin/companies/{companyId}')
    ->middleware(['auth', 'permission:run verification checks'])
    ->whereNumber('companyId')
    ->group(function () {
        Route::post('/checks/run', [ComplianceController::class, 'runVerificationCheck'])->name('admin.companies.checks.run');
    });

use App\Http\Controllers\Company\VerificationQueueController;

/*
|--------------------------------------------------------------------------
| Admin — Verification Queue
|--------------------------------------------------------------------------
*/
Route::prefix('admin/compliance')
    ->middleware(['auth', 'permission:view company compliance'])
    ->group(function () {
        Route::get('/',       [VerificationQueueController::class, 'index'])->name('admin.compliance.index');
        Route::get('/data',   [VerificationQueueController::class, 'getQueue'])->name('admin.compliance.data');
        Route::get('/stats',  [VerificationQueueController::class, 'getStats'])->name('admin.compliance.stats');
    });


use App\Http\Controllers\Company\BankAccountController;

/*
|--------------------------------------------------------------------------
| Admin — Company Bank Accounts
|--------------------------------------------------------------------------
*/

// Read
Route::prefix('admin/companies/{companyId}')
    ->middleware(['auth', 'permission:view company bank accounts'])
    ->whereNumber('companyId')
    ->group(function () {
        Route::get('/bank-accounts', [BankAccountController::class, 'getBankAccounts'])
            ->name('admin.companies.bank-accounts.index');
    });

// Create
Route::prefix('admin/companies/{companyId}')
    ->middleware(['auth', 'permission:manage company bank accounts'])
    ->whereNumber('companyId')
    ->group(function () {
        Route::post('/bank-accounts', [BankAccountController::class, 'storeBankAccount'])
            ->name('admin.companies.bank-accounts.store');
    });

// Update / delete / status / default
Route::prefix('admin')
    ->middleware(['auth', 'permission:manage company bank accounts'])
    ->group(function () {
        Route::put('/bank-accounts/{id}',               [BankAccountController::class, 'updateBankAccount'])->whereNumber('id')->name('admin.bank-accounts.update');
        Route::delete('/bank-accounts/{id}',            [BankAccountController::class, 'deleteBankAccount'])->whereNumber('id')->name('admin.bank-accounts.delete');
        Route::patch('/bank-accounts/{id}/set-default', [BankAccountController::class, 'setDefault'])->whereNumber('id')->name('admin.bank-accounts.set-default');
        Route::patch('/bank-accounts/{id}/status',      [BankAccountController::class, 'changeStatus'])->whereNumber('id')->name('admin.bank-accounts.status');
    });


    use App\Http\Controllers\Company\ApiKeyController;

/*
|--------------------------------------------------------------------------
| Admin — Company API Keys & Logs
|--------------------------------------------------------------------------
*/

// Read
Route::prefix('admin/companies/{companyId}')
    ->middleware(['auth', 'permission:view api keys'])
    ->whereNumber('companyId')
    ->group(function () {
        Route::get('/api-keys', [ApiKeyController::class, 'getApiKeys'])
            ->name('admin.companies.api-keys.index');
    });

Route::prefix('admin/companies/{companyId}')
    ->middleware(['auth', 'permission:view api request logs'])
    ->whereNumber('companyId')
    ->group(function () {
        Route::get('/api-logs', [ApiKeyController::class, 'getApiLogs'])
            ->name('admin.companies.api-logs.index');
    });

Route::prefix('admin')
    ->middleware(['auth', 'permission:view api request logs'])
    ->group(function () {
        Route::get('/api-logs/{id}', [ApiKeyController::class, 'getApiLog'])
            ->whereNumber('id')
            ->name('admin.api-logs.show');
    });

// Create
Route::prefix('admin/companies/{companyId}')
    ->middleware(['auth', 'permission:create api keys'])
    ->whereNumber('companyId')
    ->group(function () {
        Route::post('/api-keys', [ApiKeyController::class, 'storeApiKey'])
            ->name('admin.companies.api-keys.store');
    });

// Update
Route::prefix('admin')
    ->middleware(['auth', 'permission:create api keys'])
    ->group(function () {
        Route::patch('/api-keys/{id}/rotate', [ApiKeyController::class, 'rotateApiKey'])
            ->whereNumber('id')
            ->name('admin.api-keys.rotate');
    });

Route::prefix('admin')
    ->middleware(['auth', 'permission:create api keys'])
    ->group(function () {
        Route::patch('/api-keys/{id}', [ApiKeyController::class, 'updateApiKey'])
            ->whereNumber('id')
            ->name('admin.api-keys.update');
    });

// Revoke (destructive — could gate separately)
Route::prefix('admin')
    ->middleware(['auth', 'permission:revoke api keys'])
    ->group(function () {
        Route::delete('/api-keys/{id}', [ApiKeyController::class, 'revokeApiKey'])
            ->whereNumber('id')
            ->name('admin.api-keys.revoke');
    });



use App\Http\Controllers\Company\WebhookController;

/*
|--------------------------------------------------------------------------
| Admin — Webhooks
|--------------------------------------------------------------------------
*/

// Endpoints — read
Route::prefix('admin/companies/{companyId}')
    ->middleware(['auth', 'permission:view webhooks'])
    ->whereNumber('companyId')
    ->group(function () {
        Route::get('/webhook-endpoints', [WebhookController::class, 'getEndpoints'])->name('admin.companies.webhook-endpoints.index');
    });

// Endpoints — create
Route::prefix('admin/companies/{companyId}')
    ->middleware(['auth', 'permission:create webhooks'])
    ->whereNumber('companyId')
    ->group(function () {
        Route::post('/webhook-endpoints', [WebhookController::class, 'storeEndpoint'])->name('admin.companies.webhook-endpoints.store');
    });

// Endpoints — update / toggle
Route::prefix('admin')
    ->middleware(['auth', 'permission:edit webhooks'])
    ->group(function () {
        Route::put('/webhook-endpoints/{id}',      [WebhookController::class, 'updateEndpoint'])->whereNumber('id')->name('admin.webhook-endpoints.update');
        Route::patch('/webhook-endpoints/{id}/toggle', [WebhookController::class, 'toggleEndpoint'])->whereNumber('id')->name('admin.webhook-endpoints.toggle');
    });

// Endpoints — rotate secret (destructive)
Route::prefix('admin')
    ->middleware(['auth', 'permission:create webhooks'])
    ->group(function () {
        Route::patch('/webhook-endpoints/{id}/rotate-secret', [WebhookController::class, 'rotateSecret'])->whereNumber('id')->name('admin.webhook-endpoints.rotate-secret');
    });

// Endpoints — delete
Route::prefix('admin')
    ->middleware(['auth', 'permission:delete webhooks'])
    ->group(function () {
        Route::delete('/webhook-endpoints/{id}', [WebhookController::class, 'deleteEndpoint'])->whereNumber('id')->name('admin.webhook-endpoints.delete');
    });

// Event types — canonical list
Route::middleware(['auth', 'permission:view webhooks'])
    ->get('/admin/webhook-event-types', [WebhookController::class, 'getEventTypes'])
    ->name('admin.webhook-event-types');

// Deliveries — logs
Route::prefix('admin/companies/{companyId}')
    ->middleware(['auth', 'permission:view webhook deliveries'])
    ->whereNumber('companyId')
    ->group(function () {
        Route::get('/webhook-deliveries', [WebhookController::class, 'getDeliveries'])->name('admin.companies.webhook-deliveries.index');
    });

Route::prefix('admin')
    ->middleware(['auth', 'permission:view webhook deliveries'])
    ->group(function () {
        Route::get('/webhook-deliveries/{id}', [WebhookController::class, 'getDelivery'])->whereNumber('id')->name('admin.webhook-deliveries.show');
    });

Route::prefix('admin')
    ->middleware(['auth', 'permission:replay webhook deliveries'])
    ->group(function () {
        Route::post('/webhook-deliveries/{id}/retry', [WebhookController::class, 'retryDelivery'])->whereNumber('id')->name('admin.webhook-deliveries.retry');
    });



use App\Http\Controllers\Company\CustomerController;

/*
|--------------------------------------------------------------------------
| Admin — Customers
|--------------------------------------------------------------------------
*/

// Read
Route::prefix('admin/companies/{companyId}')
    ->middleware(['auth', 'permission:view customers'])
    ->whereNumber('companyId')
    ->group(function () {
        Route::get('/customers', [CustomerController::class, 'getCustomers'])->name('admin.companies.customers.index');
    });

Route::prefix('admin')
    ->middleware(['auth', 'permission:view customers'])
    ->group(function () {
        Route::get('/customers/{id}', [CustomerController::class, 'getCustomer'])->whereNumber('id')->name('admin.customers.show');
        Route::get('/customers/{customerId}/payment-methods', [CustomerController::class, 'getPaymentMethods'])->whereNumber('customerId')->name('admin.customers.payment-methods.index');
    });

// Create
Route::prefix('admin/companies/{companyId}')
    ->middleware(['auth', 'permission:create customers'])
    ->whereNumber('companyId')
    ->group(function () {
        Route::post('/customers', [CustomerController::class, 'storeCustomer'])->name('admin.companies.customers.store');
    });

// Update
Route::prefix('admin')
    ->middleware(['auth', 'permission:edit customers'])
    ->group(function () {
        Route::put('/customers/{id}', [CustomerController::class, 'updateCustomer'])->whereNumber('id')->name('admin.customers.update');
        Route::patch('/customers/{id}/toggle-block', [CustomerController::class, 'toggleBlock'])->whereNumber('id')->name('admin.customers.toggle-block');
    });

// Delete
Route::prefix('admin')
    ->middleware(['auth', 'permission:delete customers'])
    ->group(function () {
        Route::delete('/customers/{id}', [CustomerController::class, 'deleteCustomer'])->whereNumber('id')->name('admin.customers.delete');
    });

// Addresses
Route::prefix('admin/customers/{customerId}')
    ->middleware(['auth', 'permission:edit customers'])
    ->whereNumber('customerId')
    ->group(function () {
        Route::post('/addresses', [CustomerController::class, 'storeAddress'])->name('admin.customers.addresses.store');
    });

Route::prefix('admin')
    ->middleware(['auth', 'permission:edit customers'])
    ->group(function () {
        Route::put('/addresses/{id}', [CustomerController::class, 'updateAddress'])->whereNumber('id')->name('admin.addresses.update');
        Route::delete('/addresses/{id}', [CustomerController::class, 'deleteAddress'])->whereNumber('id')->name('admin.addresses.delete');
    });

// Payment methods — manage (not create — created via API)
Route::prefix('admin')
    ->middleware(['auth', 'permission:manage payment methods'])
    ->group(function () {
        Route::patch('/payment-methods/{id}/set-default', [CustomerController::class, 'setDefaultPaymentMethod'])->whereNumber('id')->name('admin.payment-methods.set-default');
        Route::patch('/payment-methods/{id}/revoke', [CustomerController::class, 'revokePaymentMethod'])->whereNumber('id')->name('admin.payment-methods.revoke');
    });

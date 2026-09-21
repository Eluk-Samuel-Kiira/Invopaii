<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\{ UserController, RoleController, PermissionController };

/*
|--------------------------------------------------------------------------
| Admin — Permissions
|--------------------------------------------------------------------------
*/
Route::prefix('admin')
    ->middleware(['auth', 'permission:view permissions'])
    ->group(function () {
        Route::get('/permissions', [PermissionController::class, 'permissions'])->name('admin.permissions');
        Route::get('/permissions/data', [PermissionController::class, 'getPermissions'])->name('admin.permissions.data');
    });

Route::prefix('admin')
    ->middleware(['auth', 'permission:manage permissions'])
    ->group(function () {
        Route::post('/permissions', [PermissionController::class, 'storePermission'])->name('admin.permissions.store');
        Route::put('/permissions/{id}', [PermissionController::class, 'updatePermission'])->name('admin.permissions.update');
        Route::delete('/permissions/{id}', [PermissionController::class, 'deletePermission'])->name('admin.permissions.delete');
    });

/*
|--------------------------------------------------------------------------
| Admin — Roles
|--------------------------------------------------------------------------
*/
Route::prefix('admin')
    ->middleware(['auth', 'permission:view roles'])
    ->group(function () {
        Route::get('/roles', [RoleController::class, 'index'])->name('admin.roles');
        Route::get('/roles/data', [RoleController::class, 'getRoles'])->name('admin.roles.data');
        Route::get('/roles/permissions/all', [RoleController::class, 'getPermissions'])->name('admin.roles.permissions');
        Route::get('/roles/{id}', [RoleController::class, 'getRole'])->name('admin.roles.get');
        Route::get('/roles/{id}/users', [RoleController::class, 'getRoleUsers'])->name('admin.roles.users');
    });

Route::prefix('admin')
    ->middleware(['auth', 'permission:manage roles'])
    ->group(function () {
        Route::post('/roles', [RoleController::class, 'storeRole'])->name('admin.roles.store');
        Route::put('/roles/{id}', [RoleController::class, 'updateRole'])->name('admin.roles.update');
        Route::delete('/roles/{id}', [RoleController::class, 'deleteRole'])->name('admin.roles.delete');
    });


    
/*
|--------------------------------------------------------------------------
| Admin — Users
|--------------------------------------------------------------------------
*/
Route::prefix('admin')
    ->middleware(['auth', 'permission:view users'])
    ->group(function () {
        Route::get('/users',                  [UserController::class, 'index'])->name('users.index');
        Route::get('/users/data',             [UserController::class, 'getUsers'])->name('users.data');
        Route::get('/users/roles/all',        [UserController::class, 'getRoles'])->name('users.roles');
        Route::get('/users/permissions/all',  [UserController::class, 'getPermissions'])->name('users.permissions.all');
        Route::get('/users/{id}/permissions', [UserController::class, 'getUserPermissions'])->whereNumber('id')->name('users.permissions');
        Route::get('/users/{id}/detail',      [UserController::class, 'getUserDetail'])->whereNumber('id')->name('users.detail');
        Route::get('/users/{id}',             [UserController::class, 'getUser'])->whereNumber('id')->name('users.get');
    });

Route::prefix('admin')
    ->middleware(['auth', 'permission:create users'])
    ->group(function () {
        Route::post('/users', [UserController::class, 'storeUser'])->name('users.store');
    });

Route::prefix('admin')
    ->middleware(['auth', 'permission:edit users'])
    ->group(function () {
        Route::put('/users/{id}',                          [UserController::class, 'updateUser'])->whereNumber('id')->name('users.update');
        Route::patch('/users/{id}/toggle-status',          [UserController::class, 'toggleUserStatus'])->whereNumber('id')->name('users.toggle-status');
        Route::patch('/users/{id}/toggle-platform-admin',  [UserController::class, 'togglePlatformAdmin'])->whereNumber('id')->name('users.toggle-platform-admin');
        Route::patch('/users/{id}/unlock',                 [UserController::class, 'unlockUser'])->whereNumber('id')->name('users.unlock');
    });

Route::prefix('admin')
    ->middleware(['auth', 'permission:assign permissions'])
    ->group(function () {
        Route::post('/users/{id}/assign-permission', [UserController::class, 'assignPermission'])->whereNumber('id')->name('users.assign-permission');
        Route::post('/users/{id}/revoke-permission', [UserController::class, 'revokePermission'])->whereNumber('id')->name('users.revoke-permission');
    });

Route::prefix('admin')
    ->middleware(['auth', 'permission:delete users'])
    ->group(function () {
        Route::delete('/users/{id}', [UserController::class, 'deleteUser'])->whereNumber('id')->name('users.delete');
    });



    use App\Http\Controllers\Catalog\CatalogController;

/*
|--------------------------------------------------------------------------
| Admin — Catalog (products, prices, tax rates, discounts)
|--------------------------------------------------------------------------
*/

// Products — read
Route::prefix('admin/companies/{companyId}')
    ->middleware(['auth', 'permission:view products'])
    ->whereNumber('companyId')
    ->group(function () {
        Route::get('/products', [CatalogController::class, 'getProducts'])->name('admin.companies.products.index');
    });

Route::prefix('admin')
    ->middleware(['auth', 'permission:view products'])
    ->group(function () {
        Route::get('/products/{id}', [CatalogController::class, 'getProduct'])->whereNumber('id')->name('admin.products.show');
        Route::get('/products/{productId}/prices', [CatalogController::class, 'getPrices'])->whereNumber('productId')->name('admin.products.prices.index');
    });

// Products — create / update / delete
Route::prefix('admin/companies/{companyId}')
    ->middleware(['auth', 'permission:create products'])
    ->whereNumber('companyId')
    ->group(function () {
        Route::post('/products', [CatalogController::class, 'storeProduct'])->name('admin.companies.products.store');
    });

Route::prefix('admin')
    ->middleware(['auth', 'permission:edit products'])
    ->group(function () {
        Route::put('/products/{id}', [CatalogController::class, 'updateProduct'])->whereNumber('id')->name('admin.products.update');
    });

Route::prefix('admin')
    ->middleware(['auth', 'permission:delete products'])
    ->group(function () {
        Route::delete('/products/{id}', [CatalogController::class, 'deleteProduct'])->whereNumber('id')->name('admin.products.delete');
    });

// Prices
Route::prefix('admin/products/{productId}')
    ->middleware(['auth', 'permission:create prices'])
    ->whereNumber('productId')
    ->group(function () {
        Route::post('/prices', [CatalogController::class, 'storePrice'])->name('admin.products.prices.store');
    });

Route::prefix('admin')
    ->middleware(['auth', 'permission:edit prices'])
    ->group(function () {
        Route::put('/prices/{id}', [CatalogController::class, 'updatePrice'])->whereNumber('id')->name('admin.prices.update');
    });

Route::prefix('admin')
    ->middleware(['auth', 'permission:delete prices'])
    ->group(function () {
        Route::delete('/prices/{id}', [CatalogController::class, 'deletePrice'])->whereNumber('id')->name('admin.prices.delete');
    });

// Tax rates
Route::prefix('admin/companies/{companyId}')
    ->middleware(['auth', 'permission:view tax rates'])
    ->whereNumber('companyId')
    ->group(function () {
        Route::get('/tax-rates', [CatalogController::class, 'getTaxRates'])->name('admin.companies.tax-rates.index');
    });

Route::prefix('admin/companies/{companyId}')
    ->middleware(['auth', 'permission:manage tax rates'])
    ->whereNumber('companyId')
    ->group(function () {
        Route::post('/tax-rates', [CatalogController::class, 'storeTaxRate'])->name('admin.companies.tax-rates.store');
    });

Route::prefix('admin')
    ->middleware(['auth', 'permission:manage tax rates'])
    ->group(function () {
        Route::put('/tax-rates/{id}', [CatalogController::class, 'updateTaxRate'])->whereNumber('id')->name('admin.tax-rates.update');
        Route::delete('/tax-rates/{id}', [CatalogController::class, 'deleteTaxRate'])->whereNumber('id')->name('admin.tax-rates.delete');
    });

// Discounts
Route::prefix('admin/companies/{companyId}')
    ->middleware(['auth', 'permission:view discounts'])
    ->whereNumber('companyId')
    ->group(function () {
        Route::get('/discounts', [CatalogController::class, 'getDiscounts'])->name('admin.companies.discounts.index');
    });

Route::prefix('admin/companies/{companyId}')
    ->middleware(['auth', 'permission:create discounts'])
    ->whereNumber('companyId')
    ->group(function () {
        Route::post('/discounts', [CatalogController::class, 'storeDiscount'])->name('admin.companies.discounts.store');
        Route::post('/discounts/validate', [CatalogController::class, 'validateDiscountCode'])->name('admin.companies.discounts.validate');
    });

Route::prefix('admin')
    ->middleware(['auth', 'permission:edit discounts'])
    ->group(function () {
        Route::put('/discounts/{id}', [CatalogController::class, 'updateDiscount'])->whereNumber('id')->name('admin.discounts.update');
    });

Route::prefix('admin')
    ->middleware(['auth', 'permission:delete discounts'])
    ->group(function () {
        Route::delete('/discounts/{id}', [CatalogController::class, 'deleteDiscount'])->whereNumber('id')->name('admin.discounts.delete');
    });




    use App\Http\Controllers\Payment\InvoiceController;

/*
|--------------------------------------------------------------------------
| Admin — Invoices
|--------------------------------------------------------------------------
*/

// Read
Route::prefix('admin/companies/{companyId}')
    ->middleware(['auth', 'permission:view invoices'])
    ->whereNumber('companyId')
    ->group(function () {
        Route::get('/invoices', [InvoiceController::class, 'getInvoices'])->name('admin.companies.invoices.index');
    });

Route::prefix('admin')
    ->middleware(['auth', 'permission:view invoices'])
    ->group(function () {
        Route::get('/invoices/{id}', [InvoiceController::class, 'getInvoice'])->whereNumber('id')->name('admin.invoices.show');
    });

// Create
Route::prefix('admin/companies/{companyId}')
    ->middleware(['auth', 'permission:create invoices'])
    ->whereNumber('companyId')
    ->group(function () {
        Route::post('/invoices', [InvoiceController::class, 'storeInvoice'])->name('admin.companies.invoices.store');
    });

// Update
Route::prefix('admin')
    ->middleware(['auth', 'permission:edit invoices'])
    ->group(function () {
        Route::put('/invoices/{id}', [InvoiceController::class, 'updateInvoice'])->whereNumber('id')->name('admin.invoices.update');
    });

// Delete
Route::prefix('admin')
    ->middleware(['auth', 'permission:delete invoices'])
    ->group(function () {
        Route::delete('/invoices/{id}', [InvoiceController::class, 'deleteInvoice'])->whereNumber('id')->name('admin.invoices.delete');
    });

// Lifecycle actions
Route::prefix('admin')
    ->middleware(['auth', 'permission:edit invoices'])
    ->group(function () {
        Route::patch('/invoices/{id}/finalize', [InvoiceController::class, 'finalizeInvoice'])->whereNumber('id')->name('admin.invoices.finalize');
        Route::patch('/invoices/{id}/void',     [InvoiceController::class, 'voidInvoice'])->whereNumber('id')->name('admin.invoices.void');
        Route::patch('/invoices/{id}/mark-paid',[InvoiceController::class, 'markPaid'])->whereNumber('id')->name('admin.invoices.mark-paid');
        Route::post('/invoices/{id}/duplicate', [InvoiceController::class, 'duplicateInvoice'])->whereNumber('id')->name('admin.invoices.duplicate');
    });

// Send — separate permission in case you want gate it independently
Route::prefix('admin')
    ->middleware(['auth', 'permission:send invoices'])
    ->group(function () {
        Route::patch('/invoices/{id}/send', [InvoiceController::class, 'sendInvoice'])->whereNumber('id')->name('admin.invoices.send');
    });


Route::prefix('admin')->middleware(['auth', 'permission:view payment links'])->group(function () {
    Route::get('/company2', [\App\Http\Controllers\Payment\PaymentLinkController::class, 'index'])->name('admin.company2.index');
    Route::get('/company2/data', [\App\Http\Controllers\Payment\PaymentLinkController::class, 'getCompanies'])->name('admin.company2.data');
});



use App\Http\Controllers\Payment\PaymentLinkController;

/*
|--------------------------------------------------------------------------
| Admin — Payment Links (per company, company2 flow)
|--------------------------------------------------------------------------
*/

Route::prefix('admin/company2')
    ->middleware(['auth', 'permission:view payment links'])
    ->group(function () {
        Route::get('/', [PaymentLinkController::class, 'index'])->name('admin.company2.index');
        Route::get('/data', [PaymentLinkController::class, 'getCompanies'])->name('admin.company2.data');

        // Stats — must come before any /{companyId} catch-all
        Route::get('/{companyId}/payment-links/stats', [PaymentLinkController::class, 'getCompanyStats'])
            ->whereNumber('companyId')
            ->name('admin.company2.payment-links.stats');

        // Per-company list
        Route::get('/{companyId}/payment-links', [PaymentLinkController::class, 'getPaymentLinks'])
            ->whereNumber('companyId')
            ->name('admin.company2.payment-links.index');
    });

// Create
Route::prefix('admin/company2/payment-links')
    ->middleware(['auth', 'permission:create payment links'])
    ->name('admin.company2.payment-links.')
    ->group(function () {
        Route::post('/{companyId}', [PaymentLinkController::class, 'storePaymentLink'])
            ->whereNumber('companyId')->name('store');
    });

// Edit / toggle / duplicate
Route::prefix('admin/company2/payment-links')
    ->middleware(['auth', 'permission:edit payment links'])
    ->name('admin.company2.payment-links.')
    ->group(function () {
        Route::get('/{id}',            [PaymentLinkController::class, 'getPaymentLink'])->whereNumber('id')->name('show');
        Route::put('/{id}',            [PaymentLinkController::class, 'updatePaymentLink'])->whereNumber('id')->name('update');
        Route::patch('/{id}/toggle',   [PaymentLinkController::class, 'togglePaymentLink'])->whereNumber('id')->name('toggle');
        Route::post('/{id}/duplicate', [PaymentLinkController::class, 'duplicatePaymentLink'])->whereNumber('id')->name('duplicate');
    });

// Delete
Route::prefix('admin/company2/payment-links')
    ->middleware(['auth', 'permission:delete payment links'])
    ->group(function () {
        Route::delete('/{id}', [PaymentLinkController::class, 'deletePaymentLink'])
            ->whereNumber('id')
            ->name('admin.company2.payment-links.delete');
    });




use App\Http\Controllers\Payment\SubscriptionController;

/*
|--------------------------------------------------------------------------
| Admin — Subscriptions (company-scoped)
|--------------------------------------------------------------------------
*/

Route::prefix('admin/company2')
    ->middleware(['auth', 'permission:view subscriptions'])
    ->group(function () {
        Route::get('/{companyId}/subscriptions', [SubscriptionController::class, 'getSubscriptions'])
            ->whereNumber('companyId')
            ->name('admin.company2.subscriptions.index');

        Route::get('/{companyId}/subscriptions/stats', [SubscriptionController::class, 'getStats'])
            ->whereNumber('companyId')
            ->name('admin.company2.subscriptions.stats');
    });

Route::prefix('admin/company2/subscriptions')
    ->middleware(['auth', 'permission:create subscriptions'])
    ->group(function () {
        Route::post('/{companyId}', [SubscriptionController::class, 'storeSubscription'])
            ->whereNumber('companyId')
            ->name('admin.company2.subscriptions.store');
    });

Route::prefix('admin/company2/subscriptions')
    ->middleware(['auth', 'permission:edit subscriptions'])
    ->group(function () {
        Route::get('/{id}',            [SubscriptionController::class, 'getSubscription'])->whereNumber('id')->name('admin.company2.subscriptions.show');
        Route::put('/{id}',            [SubscriptionController::class, 'updateSubscription'])->whereNumber('id')->name('admin.company2.subscriptions.update');
        Route::patch('/{id}/cancel',   [SubscriptionController::class, 'cancelSubscription'])->whereNumber('id')->name('admin.company2.subscriptions.cancel');
        Route::patch('/{id}/resume',   [SubscriptionController::class, 'resumeSubscription'])->whereNumber('id')->name('admin.company2.subscriptions.resume');
        Route::patch('/{id}/pause',    [SubscriptionController::class, 'pauseSubscription'])->whereNumber('id')->name('admin.company2.subscriptions.pause');
        Route::post('/{id}/bill-now',  [SubscriptionController::class, 'billNow'])->whereNumber('id')->name('admin.company2.subscriptions.bill-now');
    });

Route::prefix('admin/company2/subscriptions')
    ->middleware(['auth', 'permission:delete subscriptions'])
    ->group(function () {
        Route::delete('/{id}', [SubscriptionController::class, 'deleteSubscription'])->whereNumber('id')->name('admin.company2.subscriptions.delete');
    });
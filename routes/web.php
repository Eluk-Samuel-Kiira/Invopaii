<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Admin\{ DashboardController };
use Illuminate\Support\Facades\Route;


Route::get('/', [DashboardController::class, 'index'])->name('home');
Route::get('/docs', [DashboardController::class, 'docs'])->name('docs');

Route::get('/login', [DashboardController::class, 'login'])->name('login');


// Route::get('/dashboard', function () {
//     return view('dashboard');
// })->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'dashboard'])->name('admin.dashboard');
});


Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    Route::post('/profile/avatar', [ProfileController::class, 'updateAvatar'])->name('profile.avatar.update');
});


Route::prefix('docs')->name('docs.')->group(function () {
    Route::get('/', fn () => view('docs.index'))->name('index');
    Route::get('/api', fn () => view('docs.api'))->name('api');
    Route::get('/webhooks', fn () => view('docs.webhooks'))->name('webhooks');

    // Architecture — internal reference
    Route::get('/architecture/webhook-delivery', fn () => view('docs.architecture.webhook-delivery'))
        ->name('architecture.webhook-delivery');
});



require __DIR__.'/auth.php';
require __DIR__.'/user.php';
require __DIR__.'/reference.php';
require __DIR__.'/payment.php';

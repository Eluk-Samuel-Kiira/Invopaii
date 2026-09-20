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
        Route::get('/users', [UserController::class, 'index'])->name('users.index');
        Route::get('/users/data', [UserController::class, 'getUsers'])->name('users.data');
        Route::get('/users/roles/all', [UserController::class, 'getRoles'])->name('users.roles');
        Route::get('/users/permissions/all', [UserController::class, 'getPermissions'])->name('users.permissions.all');
        Route::get('/users/{id}/permissions', [UserController::class, 'getUserPermissions'])->name('users.permissions');
        Route::get('/users/{id}', [UserController::class, 'getUser'])->name('users.get');
    });

Route::prefix('admin')
    ->middleware(['auth', 'permission:create users'])
    ->group(function () {
        Route::post('/users', [UserController::class, 'storeUser'])->name('users.store');
    });

Route::prefix('admin')
    ->middleware(['auth', 'permission:edit users'])
    ->group(function () {
        Route::put('/users/{id}', [UserController::class, 'updateUser'])->name('users.update');
        Route::patch('/users/{id}/toggle-status', [UserController::class, 'toggleUserStatus'])->name('users.toggle-status');
        Route::post('/users/{id}/assign-permission', [UserController::class, 'assignPermission'])->name('users.assign-permission');
        Route::post('/users/{id}/revoke-permission', [UserController::class, 'revokePermission'])->name('users.revoke-permission');
    });

Route::prefix('admin')
    ->middleware(['auth', 'permission:delete users'])
    ->group(function () {
        Route::delete('/users/{id}', [UserController::class, 'deleteUser'])->name('users.delete');
    });
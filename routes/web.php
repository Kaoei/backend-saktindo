<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\WebCustomizationController;
use App\Models\User;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/dashboard');

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'create'])->name('login');
    Route::post('/login', [AuthController::class, 'store'])->name('login.store');
});

Route::post('/logout', [AuthController::class, 'destroy'])
    ->middleware('auth')
    ->name('logout');

Route::get('/dashboard', function () {
    return view('dashboard.home');
})->middleware('auth')->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/users', [UserController::class, 'index'])->name('users.index');

    Route::get('/users/create', [UserController::class, 'create'])
        ->middleware('role:'.User::ROLE_SUPER_ADMIN)
        ->name('users.create');

    Route::post('/users', [UserController::class, 'store'])
        ->middleware('role:'.User::ROLE_SUPER_ADMIN)
        ->name('users.store');

    Route::get('/users/{user}/edit', [UserController::class, 'edit'])->name('users.edit');
    Route::put('/users/{user}', [UserController::class, 'update'])->name('users.update');

    Route::delete('/users/{user}', [UserController::class, 'destroy'])
        ->middleware('role:'.User::ROLE_SUPER_ADMIN)
        ->name('users.destroy');

    Route::prefix('roles')->name('roles.')->middleware('role:'.User::ROLE_SUPER_ADMIN)->group(function () {
        Route::get('/', [RoleController::class, 'index'])->name('index');
        Route::get('/create', [RoleController::class, 'create'])->name('create');
        Route::post('/', [RoleController::class, 'store'])->name('store');
        Route::get('/{role}/edit', [RoleController::class, 'edit'])->name('edit');
        Route::put('/{role}', [RoleController::class, 'update'])->name('update');
        Route::delete('/{role}', [RoleController::class, 'destroy'])->name('destroy');
    });

    Route::get('/web-customization', [WebCustomizationController::class, 'edit'])
        ->middleware('role:'.User::ROLE_SUPER_ADMIN)
        ->name('web-customization.edit');

    Route::put('/web-customization', [WebCustomizationController::class, 'update'])
        ->middleware('role:'.User::ROLE_SUPER_ADMIN)
        ->name('web-customization.update');

    // Product Management Routes
    Route::post('/products/import', [App\Http\Controllers\ProductController::class, 'import'])->name('products.import');
    Route::get('/products/export', [App\Http\Controllers\ProductController::class, 'export'])->name('products.export');
    Route::resource('/products', App\Http\Controllers\ProductController::class);
});


<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\masterCustomerController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\RakController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\SupplierController;
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

    Route::prefix('master-customer')->name('master-customer.')->middleware('role:' . User::ROLE_SUPER_ADMIN)->group(function () {
        Route::get('/', [masterCustomerController::class, 'index'])->name('index');
        Route::get('/create', [masterCustomerController::class, 'create'])->name('create');
        Route::post('/', [masterCustomerController::class, 'store'])->name('store');
        Route::get('/{customer}/edit', [masterCustomerController::class, 'edit'])->name('edit');
        Route::put('/{customer}', [masterCustomerController::class, 'update'])->name('update');
        Route::delete('/{customer}', [masterCustomerController::class, 'destroy'])->name('destroy');
        Route::get('/import', [masterCustomerController::class, 'importPage'])->name('importPage');
        Route::post('/import', [masterCustomerController::class, 'import'])->name('import');
        Route::get('/export', [masterCustomerController::class, 'export'])->name('export');
        Route::get('/tamplate', [masterCustomerController::class, 'tamplate'])->name('tamplate');
    });
    Route::prefix('supplier')->name('supplier.')->middleware('role:' . User::ROLE_SUPER_ADMIN)->group(function () {
        Route::get('/', [SupplierController::class, 'index'])->name('index');
        Route::get('/create', [SupplierController::class, 'create'])->name('create');
        Route::post('/', [SupplierController::class, 'store'])->name('store');
        Route::get('/{supplier}/edit', [SupplierController::class, 'edit'])->name('edit');
        Route::put('/{supplier}', [SupplierController::class, 'update'])->name('update');
        Route::delete('/{supplier}', [SupplierController::class, 'destroy'])->name('destroy');
    });
    Route::prefix('rak')->name('rak.')->middleware('role:' . User::ROLE_SUPER_ADMIN)->group(function () {
        Route::get('/', [RakController::class, 'index'])->name('index');
        Route::get('/create', [RakController::class, 'create'])->name('create');
        Route::post('/', [RakController::class, 'store'])->name('store');
        Route::get('/{customer}/edit', [RakController::class, 'edit'])->name('edit');
        Route::put('/{customer}', [RakController::class, 'update'])->name('update');
        Route::delete('/{customer}', [RakController::class, 'destroy'])->name('destroy');
    });
    Route::prefix('product')->name('product.')->middleware('role:' . User::ROLE_SUPER_ADMIN)->group(function () {
        Route::get('/', [ProductController::class, 'index'])->name('index');
        Route::get('/create', [ProductController::class, 'create'])->name('create');
        Route::post('/', [ProductController::class, 'store'])->name('store');
        Route::get('/{customer}/edit', [ProductController::class, 'edit'])->name('edit');
        Route::put('/{customer}', [ProductController::class, 'update'])->name('update');
        Route::delete('/{customer}', [ProductController::class, 'destroy'])->name('destroy');
    });

});



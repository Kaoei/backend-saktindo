<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\masterCustomerController;
use App\Http\Controllers\ActivityLogController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\SessionController;
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
})->middleware(['auth', 'permission:dashboard'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/users', [UserController::class, 'index'])->middleware('permission:users.view')->name('users.index');

    Route::get('/users/create', [UserController::class, 'create'])
        ->middleware('permission:users.create')
        ->name('users.create');

    Route::post('/users', [UserController::class, 'store'])
        ->middleware('permission:users.create')
        ->name('users.store');

    Route::get('/users/{user}/edit', [UserController::class, 'edit'])->middleware('permission:users.edit')->name('users.edit');
    Route::put('/users/{user}', [UserController::class, 'update'])->middleware('permission:users.edit')->name('users.update');
    Route::get('/users/{user}/reset-password', [UserController::class, 'resetPassword'])->middleware('permission:users.edit')->name('users.reset-password');
    Route::put('/users/{user}/reset-password', [UserController::class, 'updatePassword'])->middleware('permission:users.edit')->name('users.update-password');

    Route::delete('/users/{user}', [UserController::class, 'destroy'])
        ->middleware('permission:users.delete')
        ->name('users.destroy');

    Route::prefix('roles')->name('roles.')->middleware('permission:roles.manage')->group(function () {
        Route::get('/', [RoleController::class, 'index'])->name('index');
        Route::get('/create', [RoleController::class, 'create'])->name('create');
        Route::post('/', [RoleController::class, 'store'])->name('store');
        Route::get('/{role}/edit', [RoleController::class, 'edit'])->name('edit');
        Route::put('/{role}', [RoleController::class, 'update'])->name('update');
        Route::delete('/{role}', [RoleController::class, 'destroy'])->name('destroy');
    });

    Route::prefix('suppliers')->name('suppliers.')->group(function () {
        Route::get('/', [SupplierController::class, 'index'])->middleware('permission:suppliers.view')->name('index');
        Route::get('/vendors', [SupplierController::class, 'index'])->middleware('permission:suppliers.view')->name('vendors');

        Route::get('/contacts', [SupplierController::class, 'contacts'])->middleware('permission:suppliers.view')->name('contacts');
        Route::get('/contacts/create', [SupplierController::class, 'createContact'])->middleware('permission:suppliers.create')->name('contacts.create');
        Route::post('/contacts', [SupplierController::class, 'storeContact'])->middleware('permission:suppliers.create')->name('contacts.store');
        Route::get('/contacts/{contact}/edit', [SupplierController::class, 'editContact'])->middleware('permission:suppliers.edit')->name('contacts.edit');
        Route::put('/contacts/{contact}', [SupplierController::class, 'updateContact'])->middleware('permission:suppliers.edit')->name('contacts.update');
        Route::delete('/contacts/{contact}', [SupplierController::class, 'destroyContact'])->middleware('permission:suppliers.delete')->name('contacts.destroy');

        Route::get('/products', [SupplierController::class, 'products'])->middleware('permission:suppliers.view')->name('products');
        Route::get('/products/create', [SupplierController::class, 'createProduct'])->middleware('permission:suppliers.create')->name('products.create');
        Route::post('/products', [SupplierController::class, 'storeProduct'])->middleware('permission:suppliers.create')->name('products.store');
        Route::get('/products/{product}/edit', [SupplierController::class, 'editProduct'])->middleware('permission:suppliers.edit')->name('products.edit');
        Route::put('/products/{product}', [SupplierController::class, 'updateProduct'])->middleware('permission:suppliers.edit')->name('products.update');
        Route::delete('/products/{product}', [SupplierController::class, 'destroyProduct'])->middleware('permission:suppliers.delete')->name('products.destroy');

        Route::get('/purchases', [SupplierController::class, 'purchases'])->middleware('permission:suppliers.view')->name('purchases');
        Route::get('/purchases/create', [SupplierController::class, 'createPurchase'])->middleware('permission:suppliers.create')->name('purchases.create');
        Route::post('/purchases', [SupplierController::class, 'storePurchase'])->middleware('permission:suppliers.create')->name('purchases.store');
        Route::get('/purchases/{purchaseHistory}/edit', [SupplierController::class, 'editPurchase'])->middleware('permission:suppliers.edit')->name('purchases.edit');
        Route::put('/purchases/{purchaseHistory}', [SupplierController::class, 'updatePurchase'])->middleware('permission:suppliers.edit')->name('purchases.update');
        Route::delete('/purchases/{purchaseHistory}', [SupplierController::class, 'destroyPurchase'])->middleware('permission:suppliers.delete')->name('purchases.destroy');

        Route::get('/payment-terms', [SupplierController::class, 'paymentTerms'])->middleware('permission:suppliers.view')->name('payment-terms');
        Route::get('/payment-terms/create', [SupplierController::class, 'createPaymentTerm'])->middleware('permission:suppliers.create')->name('payment-terms.create');
        Route::post('/payment-terms', [SupplierController::class, 'storePaymentTerm'])->middleware('permission:suppliers.create')->name('payment-terms.store');
        Route::get('/payment-terms/{paymentTerm}/edit', [SupplierController::class, 'editPaymentTerm'])->middleware('permission:suppliers.edit')->name('payment-terms.edit');
        Route::put('/payment-terms/{paymentTerm}', [SupplierController::class, 'updatePaymentTerm'])->middleware('permission:suppliers.edit')->name('payment-terms.update');
        Route::delete('/payment-terms/{paymentTerm}', [SupplierController::class, 'destroyPaymentTerm'])->middleware('permission:suppliers.delete')->name('payment-terms.destroy');

        Route::get('/create', [SupplierController::class, 'create'])->middleware('permission:suppliers.create')->name('create');
        Route::post('/', [SupplierController::class, 'store'])->middleware('permission:suppliers.create')->name('store');
        Route::get('/{supplier}', [SupplierController::class, 'show'])->middleware('permission:suppliers.view')->name('show');
        Route::get('/{supplier}/edit', [SupplierController::class, 'edit'])->middleware('permission:suppliers.edit')->name('edit');
        Route::put('/{supplier}', [SupplierController::class, 'update'])->middleware('permission:suppliers.edit')->name('update');
        Route::delete('/{supplier}', [SupplierController::class, 'destroy'])->middleware('permission:suppliers.delete')->name('destroy');
    });

    Route::get('/activity-logs', [ActivityLogController::class, 'index'])
        ->middleware('permission:activity_logs.view')
        ->name('activity-logs.index');

    Route::get('/sessions', [SessionController::class, 'index'])
        ->middleware('permission:sessions.manage')
        ->name('sessions.index');

    Route::delete('/sessions/{session}', [SessionController::class, 'destroy'])
        ->middleware('permission:sessions.manage')
        ->name('sessions.destroy');

    Route::get('/web-customization', [WebCustomizationController::class, 'edit'])
        ->middleware('permission:roles.manage')
        ->name('web-customization.edit');

    Route::put('/web-customization', [WebCustomizationController::class, 'update'])
        ->middleware('permission:roles.manage')
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

});

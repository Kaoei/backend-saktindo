<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\ProposalController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\UserController;
use App\Models\User;
use Illuminate\Support\Facades\Route;

// Redirect home to dashboard (auth will redirect guests to login)
Route::redirect('/', '/dashboard');

// Authentication routes
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'create'])->name('login');
    Route::post('/login', [AuthController::class, 'store'])->name('login.store');
});

Route::post('/logout', [AuthController::class, 'destroy'])
    ->middleware('auth')
    ->name('logout');

// Protected dashboard
Route::get('/dashboard', function () {
    return view('dashboard.home');
})->middleware('auth')->name('dashboard');

// Users management (protected)
Route::middleware('auth')->group(function () {
    // Users management
    Route::get('/users', [UserController::class, 'index'])->name('users.index');

    // Create + store restricted to super admin
    Route::get('/users/create', [UserController::class, 'create'])
        ->middleware('role:'.User::ROLE_SUPER_ADMIN)
        ->name('users.create');

    Route::post('/users', [UserController::class, 'store'])
        ->middleware('role:'.User::ROLE_SUPER_ADMIN)
        ->name('users.store');

    // Edit + update
    Route::get('/users/{user}/edit', [UserController::class, 'edit'])->name('users.edit');
    Route::put('/users/{user}', [UserController::class, 'update'])->name('users.update');

    // Delete restricted to super admin
    Route::delete('/users/{user}', [UserController::class, 'destroy'])
        ->middleware('role:'.User::ROLE_SUPER_ADMIN)
        ->name('users.destroy');

    // Role Management (super admin only)
    Route::prefix('roles')->name('roles.')->middleware('role:'.User::ROLE_SUPER_ADMIN)->group(function () {
        Route::get('/',              [RoleController::class, 'index'])->name('index');
        Route::get('/create',        [RoleController::class, 'create'])->name('create');
        Route::post('/',             [RoleController::class, 'store'])->name('store');
        Route::get('/{role}/edit',   [RoleController::class, 'edit'])->name('edit');
        Route::put('/{role}',        [RoleController::class, 'update'])->name('update');
        Route::delete('/{role}',     [RoleController::class, 'destroy'])->name('destroy');
    });

    // Clients management
    Route::get('/clients', [ClientController::class, 'index'])->name('clients.index');
    Route::get('/clients/create', [ClientController::class, 'create'])->name('clients.create');
    Route::post('/clients', [ClientController::class, 'store'])->name('clients.store');
    Route::get('/clients/{client}/edit', [ClientController::class, 'edit'])->name('clients.edit');
    Route::put('/clients/{client}', [ClientController::class, 'update'])->name('clients.update');
    Route::delete('/clients/{client}', [ClientController::class, 'destroy'])->name('clients.destroy');

    // Proposals management (Sales + Super Admin + Admin)
    Route::prefix('proposals')->name('proposals.')->middleware('role:'.User::ROLE_SALES.','.User::ROLE_SUPER_ADMIN.','.User::ROLE_ADMIN)->group(function () {
        Route::get('/',                             [ProposalController::class, 'index'])->name('index');
        Route::get('/create',                       [ProposalController::class, 'create'])->name('create');
        Route::post('/',                            [ProposalController::class, 'store'])->name('store');
        Route::get('/{proposal}',                   [ProposalController::class, 'show'])->name('show');
        Route::get('/{proposal}/edit',              [ProposalController::class, 'edit'])->name('edit');
        Route::put('/{proposal}',                   [ProposalController::class, 'update'])->name('update');
        Route::patch('/{proposal}/status',          [ProposalController::class, 'updateStatus'])->name('updateStatus');
        Route::post('/{proposal}/send-email',       [ProposalController::class, 'sendEmail'])->name('sendEmail');
        Route::delete('/{proposal}',                [ProposalController::class, 'destroy'])->name('destroy');
    });

    // Invoices management (Finance + Super Admin + Admin)
    Route::prefix('invoices')->name('invoices.')->middleware('role:'.User::ROLE_FINANCE.','.User::ROLE_SUPER_ADMIN.','.User::ROLE_ADMIN)->group(function () {
        Route::get('/',                             [InvoiceController::class, 'index'])->name('index');
        Route::get('/{invoice}',                    [InvoiceController::class, 'show'])->name('show');
        Route::patch('/{invoice}/mark-paid',        [InvoiceController::class, 'markPaid'])->name('markPaid');
        Route::get('/{invoice}/receipt',            [InvoiceController::class, 'receipt'])->name('receipt');
    });
});


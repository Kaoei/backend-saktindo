<?php

use App\Http\Controllers\AuthController;
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
    // List users
    Route::get('/users', [UserController::class, 'index'])->name('users.index');

    // Create + store restricted to super admin
    Route::get('/users/create', [UserController::class, 'create'])
        ->middleware('role:'.User::ROLE_SUPER_ADMIN)
        ->name('users.create');

    Route::post('/users', [UserController::class, 'store'])
        ->middleware('role:'.User::ROLE_SUPER_ADMIN)
        ->name('users.store');

    // Edit + update (accessible to authenticated; tighten later if needed)
    Route::get('/users/{user}/edit', [UserController::class, 'edit'])->name('users.edit');
    Route::put('/users/{user}', [UserController::class, 'update'])->name('users.update');

    // Delete restricted to super admin
    Route::delete('/users/{user}', [UserController::class, 'destroy'])
        ->middleware('role:'.User::ROLE_SUPER_ADMIN)
        ->name('users.destroy');
});


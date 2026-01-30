<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('dashboard.home');
});

// Frontend-only access: show login page, but do not process authentication
Route::get('/login', function () {
    return view('auth.login');
})->name('login');

Route::post('/login', function () {
    return back()->withErrors(['login' => 'Login dinonaktifkan sementara (frontend-only).']);
})->name('login.store');

// Disable real logout (no-op to keep frontend flow intact)
Route::post('/logout', function () {
    return back();
})->name('logout');

// Make dashboard accessible without auth (frontend-only)
Route::get('/dashboard', function () {
    return view('dashboard.home');
})->name('dashboard');

// Users pages: expose views directly (no backend operations)
Route::get('/users', function () {
    return view('users.index');
})->name('users.index');

Route::get('/users/create', function () {
    return view('users.create');
})->name('users.create');

Route::get('/users/{user?}/edit', function () {
    return view('users.edit');
})->name('users.edit');

// Optional placeholder for /register so the link opens a page (frontend-only)
Route::get('/register', function () {
    return response('<!DOCTYPE html><html><head><meta charset="utf-8"><title>Register</title></head><body style="font-family:sans-serif;padding:2rem;">Register (frontend-only). Form submission is disabled.</body></html>');
})->name('register');

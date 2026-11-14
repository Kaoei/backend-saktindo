<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->trustProxies(at: '*');
        $middleware->validateCsrfTokens(except: [
            'api/external-orders',
        ]);
        $middleware->alias([
            'role' => \App\Http\Middleware\EnsureRole::class,
            'permission' => \App\Http\Middleware\EnsurePermission::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (\Illuminate\Database\QueryException $e, $request) {
            $msg = \App\Support\FriendlyError::message($e);
            if ($request->expectsJson()) {
                return response()->json(['message' => $msg, 'error' => $msg], 500);
            }
            return back()->withInput()->with('error', $msg);
        });

        $exceptions->render(function (\PDOException $e, $request) {
            $msg = \App\Support\FriendlyError::message($e);
            if ($request->expectsJson()) {
                return response()->json(['message' => $msg, 'error' => $msg], 500);
            }
            return back()->withInput()->with('error', $msg);
        });
    })->create();

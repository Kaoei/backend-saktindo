<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureRole
{
    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (! $user) {
            abort(401);
        }

        // Super admin always has full access
        if ($user->role === \App\Models\User::ROLE_SUPER_ADMIN) {
            return $next($request);
        }

        // Direct role slug match
        if (in_array($user->role ?? null, $roles, true)) {
            return $next($request);
        }

        // Normalized slug match (e.g. gudang-test vs gudang_test)
        $normalizedUserRole = str_replace('-', '_', (string) ($user->role ?? ''));
        foreach ($roles as $allowedRole) {
            if ($normalizedUserRole === str_replace('-', '_', $allowedRole)) {
                return $next($request);
            }
        }

        // Permission-aware compatibility for custom roles:
        // If route requires gudang role, allow if the custom role has any Gudang permission
        if (in_array(\App\Models\User::ROLE_GUDANG, $roles, true)) {
            $gudangPermissions = [
                'rak.view', 'rak.create', 'rak.edit', 'rak.delete',
                'inbound.view', 'inbound.create', 'inbound.edit', 'inbound.delete',
                'gudang_product.view', 'gudang_product.create', 'gudang_product.delete',
                'warehouse_task.view', 'warehouse_task.create', 'warehouse_task.edit', 'warehouse_task.delete', 'warehouse_task.process', 'warehouse_task.complete',
                'outbound.view', 'outbound.create', 'outbound.print', 'outbound.delete',
                'stock_card.view', 'stock_card.export',
            ];
            if ($user->hasAnyPermission($gudangPermissions)) {
                return $next($request);
            }
        }

        // If route requires sales role, allow if user has sales permissions
        if (in_array(\App\Models\User::ROLE_SALES, $roles, true)) {
            if ($user->hasPermission('sales_finance.view') || $user->hasPermission('warehouse_task.view')) {
                return $next($request);
            }
        }

        abort(403, 'Access denied.');
    }
}

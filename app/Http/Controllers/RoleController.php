<?php

namespace App\Http\Controllers;

use App\Models\Role;
use App\Support\ActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class RoleController extends Controller
{
    public function index()
    {
        $roles = Role::orderByDesc('is_system')->orderBy('name')->get();

        return view('roles.index', compact('roles'));
    }

    public function create()
    {
        $permissions      = Role::PERMISSIONS;
        $permissionGroups = Role::PERMISSION_GROUPS;

        return view('roles.create', compact('permissions', 'permissionGroups'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name'        => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string'],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['string', 'in:' . implode(',', array_keys(Role::PERMISSIONS))],
        ]);

        $slug = Str::slug($data['name'], '_');

        if (Role::where('slug', $slug)->exists()) {
            return back()->withInput()->withErrors(['name' => 'Role dengan nama ini sudah ada.']);
        }

        $role = Role::query()->create([
            'name'        => $data['name'],
            'slug'        => $slug,
            'description' => $data['description'] ?? null,
            'permissions' => $data['permissions'] ?? [],
            'is_system'   => false,
        ]);

        ActivityLogger::log('create', 'role', $role);

        return redirect()->route('roles.index')->with('status', 'Role berhasil dibuat.');
    }

    public function edit(Role $role)
    {
        $permissions      = Role::PERMISSIONS;
        $permissionGroups = Role::PERMISSION_GROUPS;

        return view('roles.edit', compact('role', 'permissions', 'permissionGroups'));
    }

    public function update(Request $request, Role $role): RedirectResponse
    {
        $data = $request->validate([
            'name'          => ['required', 'string', 'max:100'],
            'description'   => ['nullable', 'string'],
            'permissions'   => ['nullable', 'array'],
            'permissions.*' => ['string', 'in:' . implode(',', array_keys(Role::PERMISSIONS))],
        ]);

        $role->name        = $data['name'];
        $role->description = $data['description'] ?? null;
        $role->permissions = $data['permissions'] ?? [];

        // Update slug only for non-system roles
        if (! $role->is_system) {
            $role->slug = Str::slug($data['name'], '_');
        }

        $role->save();

        ActivityLogger::log('update', 'role', $role);

        return redirect()->route('roles.index')->with('status', "Role '{$role->name}' berhasil diupdate.");
    }

    public function destroy(Role $role): RedirectResponse
    {
        if ($role->slug === auth()->user()->role) {
            return redirect()->route('roles.index')->with('error', 'Tidak dapat menghapus role yang sedang Anda gunakan.');
        }

        $roleName = $role->name;
        ActivityLogger::log('delete', 'role', $role, ['name' => $roleName]);
        $role->delete();

        return redirect()->route('roles.index')->with('status', "Role '{$roleName}' berhasil dihapus.");
    }
}

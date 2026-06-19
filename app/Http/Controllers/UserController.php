<?php

namespace App\Http\Controllers;

use App\Models\Role;
use App\Models\User;
use App\Support\ActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    public function index()
    {
        $users = User::query()->orderByDesc('id')->get();

        return view('users.index', compact('users'));
    }

    public function create()
    {
        $roles  = Role::orderBy('name')->get();
        $groups = User::whereNotNull('group')->select('group')->distinct()->orderBy('group')->pluck('group');

        return view('users.create', compact('roles', 'groups'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validRoleSlugs = Role::pluck('slug')->implode(',');

        $data = $request->validate([
            'name'     => ['required', 'string', 'max:255'],
            'email'    => ['required', 'email', 'max:255', 'unique:users,email'],
            'role'     => ['required', 'in:' . $validRoleSlugs],
            'group'    => ['nullable', 'string', 'max:100'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $user = User::query()->create([
            'name'     => $data['name'],
            'email'    => $data['email'],
            'role'     => $data['role'],
            'group'    => $data['group'] ?? null,
            'password' => Hash::make($data['password']),
        ]);

        ActivityLogger::log('create', 'user', $user);

        return redirect()->route('users.index')->with('status', 'User berhasil dibuat.');
    }

    public function edit(User $user)
    {
        $roles  = Role::orderBy('name')->get();
        $groups = User::whereNotNull('group')->select('group')->distinct()->orderBy('group')->pluck('group');

        return view('users.edit', compact('user', 'roles', 'groups'));
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $validRoleSlugs = Role::pluck('slug')->implode(',');

        $data = $request->validate([
            'name'     => ['required', 'string', 'max:255'],
            'email'    => ['required', 'email', 'max:255', 'unique:users,email,' . $user->id],
            'role'     => ['required', 'in:' . $validRoleSlugs],
            'group'    => ['nullable', 'string', 'max:100'],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
        ]);

        $user->name  = $data['name'];
        $user->email = $data['email'];
        $user->role  = $data['role'];
        $user->group = $data['group'] ?? null;

        if (! empty($data['password'])) {
            $user->password = Hash::make($data['password']);
        }

        $user->save();

        ActivityLogger::log('update', 'user', $user);

        return redirect()->route('users.index')->with('status', 'User berhasil diupdate.');
    }

    public function resetPassword(User $user)
    {
        return view('users.reset-password', compact('user'));
    }

    public function updatePassword(Request $request, User $user): RedirectResponse
    {
        $data = $request->validate([
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $user->password = Hash::make($data['password']);
        $user->save();

        ActivityLogger::log('reset_password', 'user', $user);

        return redirect()->route('users.index')->with('status', 'Password user berhasil direset.');
    }

    public function destroy(User $user): RedirectResponse
    {
        ActivityLogger::log('delete', 'user', $user, ['email' => $user->email]);

        $user->delete();

        return redirect()->route('users.index')->with('status', 'User berhasil dihapus.');
    }
}

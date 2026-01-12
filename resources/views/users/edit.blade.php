@extends('layouts.dashboard', [
    'title' => 'Edit User',
    'pageTitle' => 'User Management',
    'breadcrumb' => '<li class="breadcrumb-item"><a href="'.route('dashboard').'">Home</a></li><li class="breadcrumb-item"><a href="'.route('users.index').'">User Management</a></li><li class="breadcrumb-item">Edit User</li>',
])

@section('content')
<div class="row justify-content-center">
    <div class="col-xl-6 col-lg-7 col-md-10">
        <div class="card">
            <div class="card-header d-flex align-items-center justify-content-between">
                <h5 class="mb-0">Edit User</h5>
                <a href="{{ route('users.index') }}" class="btn btn-outline-secondary btn-sm">Back</a>
            </div>
            <div class="card-body">
                @if ($errors->any())
                    <div class="alert alert-danger">
                        <ul class="mb-0">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form method="POST" action="{{ route('users.update', $user) }}">
                    @csrf
                    @method('PUT')

                    <div class="mb-3">
                        <label class="form-label">Name</label>
                        <input name="name" type="text" class="form-control" value="{{ old('name', $user->name) }}" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Email</label>
                        <input name="email" type="email" class="form-control" value="{{ old('email', $user->email) }}" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Role</label>
                        <select name="role" class="form-select" required>
                            <option value="{{ \App\Models\User::ROLE_SUPER_ADMIN }}" {{ old('role', $user->role) === \App\Models\User::ROLE_SUPER_ADMIN ? 'selected' : '' }}>Super Admin</option>
                            <option value="{{ \App\Models\User::ROLE_ADMIN }}" {{ old('role', $user->role) === \App\Models\User::ROLE_ADMIN ? 'selected' : '' }}>Admin</option>
                            <option value="{{ \App\Models\User::ROLE_TEKNISI }}" {{ old('role', $user->role) === \App\Models\User::ROLE_TEKNISI ? 'selected' : '' }}>Teknisi</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">New Password (optional)</label>
                        <input name="password" type="password" class="form-control" autocomplete="new-password">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Confirm New Password</label>
                        <input name="password_confirmation" type="password" class="form-control" autocomplete="new-password">
                    </div>

                    <button type="submit" class="btn btn-primary">Save</button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

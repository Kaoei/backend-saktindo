@extends('layouts.dashboard', [
    'title' => 'Create User',
    'pageTitle' => 'User Management',
    'breadcrumb' => '<li class="breadcrumb-item"><a href="'.route('dashboard').'">Home</a></li><li class="breadcrumb-item"><a href="'.route('users.index').'">User Management</a></li><li class="breadcrumb-item">Create User</li>',
])

@section('content')
<div class="row justify-content-center">
    <div class="col-xl-6 col-lg-7 col-md-10">
        <div class="card">
            <div class="card-header d-flex align-items-center justify-content-between">
                <h5 class="mb-0">Create User</h5>
                <a href="{{ route('users.index') }}" class="btn btn-outline-secondary btn-sm">Back</a>
            </div>
            <div class="card-body">
                @if (session('status'))
                    <div class="alert alert-success mt-4">{{ session('status') }}</div>
                @endif

                @if ($errors->any())
                    <div class="alert alert-danger">
                        <ul class="mb-0">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form method="POST" action="{{ route('users.store') }}">
                    @csrf

                    <div class="mb-3">
                        <label class="form-label">Name</label>
                        <input name="name" type="text" class="form-control" value="{{ old('name') }}" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Email</label>
                        <input name="email" type="email" class="form-control" value="{{ old('email') }}" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Role <span class="text-danger">*</span></label>
                        <select name="role" class="form-select" required>
                            <option value="" disabled {{ old('role') ? '' : 'selected' }}>Pilih role...</option>
                            @foreach($roles as $role)
                                <option value="{{ $role->slug }}" {{ old('role') === $role->slug ? 'selected' : '' }}>
                                    {{ $role->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Group</label>
                        <input name="group" type="text" class="form-control" value="{{ old('group') }}" list="groups-list">
                        <datalist id="groups-list">
                            @foreach($groups as $group)
                                <option value="{{ $group }}"></option>
                            @endforeach
                        </datalist>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Password</label>
                        <input name="password" type="password" class="form-control" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Confirm Password</label>
                        <input name="password_confirmation" type="password" class="form-control" required>
                    </div>

                    <button type="submit" class="btn btn-primary">Create</button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

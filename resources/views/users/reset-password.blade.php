@extends('layouts.dashboard', [
    'title' => 'Reset Password',
    'pageTitle' => 'User Management',
    'breadcrumb' => '<li class="breadcrumb-item"><a href="'.route('dashboard').'">Home</a></li><li class="breadcrumb-item"><a href="'.route('users.index').'">User Management</a></li><li class="breadcrumb-item">Reset Password</li>',
])

@section('content')
<div class="row justify-content-center">
    <div class="col-xl-6 col-lg-7 col-md-10">
        <div class="card">
            <div class="card-header d-flex align-items-center justify-content-between">
                <div>
                    <h5 class="mb-0">Reset Password</h5>
                    <small class="text-muted">{{ $user->name }} · {{ $user->email }}</small>
                </div>
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

                <form method="POST" action="{{ route('users.update-password', $user) }}">
                    @csrf
                    @method('PUT')

                    <div class="mb-3">
                        <label class="form-label">Password Baru</label>
                        <input name="password" type="password" class="form-control" required autocomplete="new-password">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Konfirmasi Password Baru</label>
                        <input name="password_confirmation" type="password" class="form-control" required autocomplete="new-password">
                    </div>

                    <button type="submit" class="btn btn-primary">Reset Password</button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

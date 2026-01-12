@php
    $themeBase = 'DashboardKit-main';
@endphp

@extends('layouts.auth', ['title' => 'Login'])

@section('content')
<div class="auth-wrapper">
    <div class="auth-content">
        <div class="card">
            <div class="row align-items-center text-center">
                <div class="col-md-12">
                    <div class="card-body">
                        <img src="{{ asset($themeBase.'/images/logo-dark.svg') }}" alt="" class="img-fluid mb-4">
                        <h4 class="mb-3 f-w-400">Signin</h4>

                        @if ($errors->any())
                            <div class="alert alert-danger text-start">
                                <ul class="mb-0">
                                    @foreach ($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                        <form method="POST" action="{{ route('login.store') }}">
                            @csrf

                            <div class="input-group mb-3">
                                <span class="input-group-text"><i data-feather="mail"></i></span>
                                <input name="email" type="email" class="form-control" placeholder="Email address" value="{{ old('email') }}" required autofocus>
                            </div>

                            <div class="input-group mb-4">
                                <span class="input-group-text"><i data-feather="lock"></i></span>
                                <input name="password" type="password" class="form-control" placeholder="Password" required>
                            </div>

                            <div class="form-group text-left mt-2">
                                <div class="form-check">
                                    <input name="remember" class="form-check-input" type="checkbox" id="remember" {{ old('remember') ? 'checked' : '' }}>
                                    <label class="form-check-label" for="remember">Save credentials</label>
                                </div>
                            </div>

                            <button class="btn btn-block btn-primary mb-4" type="submit">Signin</button>
                        </form>

                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

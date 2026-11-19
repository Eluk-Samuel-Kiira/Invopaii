@extends('layouts.auth')

@section('title', 'Sign In | Stardena Pay')
@section('description', 'Sign in to your Stardena account to manage payments and invoices.')
@section('content')

    <div class="text-center mb-11">
        <h1 class="text-gray-900 fw-bolder mb-3">Sign In</h1>
        <div class="text-gray-500 fw-semibold fs-6">{{ config('app.name') }} Login Page</div>
    </div>

    @if (session('status'))
        <div class="alert alert-success mb-6">{{ session('status') }}</div>
    @endif

    {{-- Quick login keys (dev/trial only) --}}
    @if (app()->environment(['local', 'testing']) || config('app.debug'))
        <div class="mb-8">
            <div class="text-muted fs-7 fw-semibold mb-3">Quick sign-in (trial accounts)</div>
            <div class="d-flex flex-wrap gap-2">
                <button type="button"
                    class="btn btn-sm btn-light-primary quick-login"
                    data-email="superadmin@lafab.com"
                    data-password="Admin@1234"
                    data-submit="1">
                    Super Admin
                </button>
            </div>
        </div>
    @endif

    <form class="form w-100" method="POST" action="{{ route('login.post') }}" id="loginForm" novalidate>
        @csrf

        {{-- Email --}}
        <div class="fv-row mb-8">
            <input type="email" name="email" id="login_email" placeholder="Email"
                value="{{ old('email') }}"
                class="form-control bg-transparent @error('email') is-invalid @enderror"
                autocomplete="username" autofocus required />
            @error('email')
                <div class="fv-plugins-message-container">
                    <div class="fv-help-block">{{ $message }}</div>
                </div>
            @enderror
        </div>

        {{-- Password --}}
        <div class="fv-row mb-3">
            <input type="password" name="password" id="login_password" placeholder="Password"
                class="form-control bg-transparent @error('password') is-invalid @enderror"
                autocomplete="current-password" required />
            @error('password')
                <div class="fv-plugins-message-container">
                    <div class="fv-help-block">{{ $message }}</div>
                </div>
            @enderror
        </div>

        {{-- Remember + Forgot --}}
        <div class="d-flex flex-stack flex-wrap gap-3 fs-base fw-semibold mb-8">
            <label class="form-check form-check-inline">
                <input class="form-check-input" type="checkbox" name="remember" id="remember_me" />
                <span class="form-check-label text-gray-700">Remember me</span>
            </label>
            @if (Route::has('password.request'))
                <a href="{{ route('password.request') }}" class="link-primary">Forgot Password?</a>
            @endif
        </div>

        {{-- Submit --}}
        <div class="d-grid mb-10">
            <button type="submit" class="btn btn-primary" id="loginSubmit">
                <span class="indicator-label">Sign In</span>
                <span class="indicator-progress">Please wait...
                    <span class="spinner-border spinner-border-sm align-middle ms-2"></span>
                </span>
            </button>
        </div>
    </form>

@endsection

@section('scripts')
    <script src="{{ asset('assets/js/custom/authentication/sign-in/general.js') }}"></script>

    <script>
        document.querySelectorAll('.quick-login').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var email = btn.dataset.email || '';
                var password = btn.dataset.password || '';
                var shouldSubmit = btn.dataset.submit === '1';

                document.getElementById('login_email').value = email;
                document.getElementById('login_password').value = password;

                if (shouldSubmit) {
                    document.getElementById('loginForm').submit();
                }
            });
        });
    </script>
@endsection
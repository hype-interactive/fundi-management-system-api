@extends('layouts.auth')
@section('auth_component')
    <div class="login">
        <div class="title">Sign In</div>
        <div class="error">
            <x-auth-session-status :status="session('status')" />
        </div>
        <form method="POST" action="{{ route('login') }}">
            @csrf
            <div class="form-group">
                <input type="email" id="email" class="form-control" placeholder="Enter email" name="email"
                    :value="old('email')" required autofocus>
                    <x-input-error :messages="$errors->get('email')" class="mt-2" />
            </div>
            <div class="form-group">
                <input id="password" type="password" class="form-control" placeholder="Enter password" type="password"
                    name="password" required autocomplete="current-password">
                    <x-input-error :messages="$errors->get('password')" class="mt-2" />
            </div>
            <button type="submit" class="btn btn-custom mt-4">{{ __('Log in') }}</button>
            <div class="text-center mt-2">
                <a href="{{ route('password.request') }}">Forgot password?</a>
            </div>
        </form>
    </div>
@endsection


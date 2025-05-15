@extends('layouts.auth')
@section('auth_component')
    <div class="password-request">
        <div class="title">Request new password</div>
        <div class="error">
            <x-auth-session-status class="mb-4" :status="session('status')" />
        </div>
        <form method="POST" action="{{ route('password.email') }}">
            @csrf

            <!-- Email Address -->
            <div class="form-group">
                <input wire:model="email" type="email" class="form-control" name="email" :value="old('email')" required
                    autofocus placeholder="Enter email">
                    <x-input-error :messages="$errors->get('email')" class="mt-2" />
            </div>
            <button type="submit" class="btn btn-custom mt-4">Submit</button>
        </form>
    </div>
@endsection

@extends('layouts.main')
@section('layout')
<div class="auth-wrapper" style="background-image: url('{{ asset('images/auth-bg.jpg') }}'); background-size: cover; background-position: center;">
    <div class="auth-form">
        <div class="header">
            <img src="{{ asset('images/logo.png') }}" alt="">
        </div>
        <div class="body">
            @yield('auth_component')
        </div>
    </div>
</div>
@endsection

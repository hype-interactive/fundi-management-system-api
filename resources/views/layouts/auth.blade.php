@extends('layouts.main')
@section('layout')
<div class="auth-wrapper">
    <div class="auth-form">
        <div class="header">
            <img src="/images/tirima.png" alt="">
        </div>
        <div class="body">
            @yield('auth_component')
        </div>
    </div>
</div>
@endsection
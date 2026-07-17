@extends('layouts.app')
@section('page')
<div class="dashboard-wrapper">
    <div class="row">
        <div class="col-md-9">
            <div class="header mb-1">
                <h2>Hello {{ Auth::user()->fname }}</h2>
                <p>Welcome back! Here's your monthly performance overview.</p>
                <small class="text-muted">
                    <i class="fas fa-calendar-alt me-1"></i>
                    All metrics display monthly trend data
                </small>
            </div>
            <div class="body">
                <livewire:platform-summary>
            </div>
            <div class="mt-4"></div>
        </div>
        <div class="col-md-3 pt-4">
            <div class="light-bg section-right summary">
                @livewire('dashboard.company-summary')
            </div>
        </div>
    </div>
    <livewire:charts />
</div>
@endsection
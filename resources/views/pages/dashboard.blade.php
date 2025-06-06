@extends('layouts.app')
@section('page')
<div class="dashboard-wrapper">
    <div class="row">
        <div class="col-md-9">
            <div class="header mb-1">
                <h2>Hello {{ Auth::user()->fname }}</h2>
                <p>Welcome back!</p>
            </div>
            <div class="body">

                <livewire:platform-summary>
            </div>
        </div>
        <div class="col-md-3">
            <div class="section-right summary light-bg ">
                @livewire('dashboard.company-summary')
            </div>
        </div>
    </div>
    <div class="mt-4"></div>

    <livewire:charts />
</div>
@endsection

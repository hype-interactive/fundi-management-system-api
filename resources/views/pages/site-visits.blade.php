@extends('layouts.app')
@section('page')
    <div class="page site-visit-wrapper">
        <div class="custom-header">
            <h2>Site Visits</h2>
            <div class="actions">
                <!-- To be added later -->
            </div>
        </div>

        @livewire('site-visits-table')
    </div>
@endsection

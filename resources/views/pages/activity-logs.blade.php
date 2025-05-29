@extends('layouts.app')
@section('page')
    <div class="page logs-wrapper ">
        <div class="body">
            <div class="custom-header">
                <h2>System logs</h2>
            </div>
            @livewire('activity-logs')
        </div>
    </div>
@endsection

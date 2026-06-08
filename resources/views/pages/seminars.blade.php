@extends('layouts.app')
@section('page')
    <div class="page site-visit-wrapper">
        <div class="custom-header">
            <h2>Seminars</h2>
        </div>
        @livewire('seminar.seminars-list-screen')
    </div>
@endsection
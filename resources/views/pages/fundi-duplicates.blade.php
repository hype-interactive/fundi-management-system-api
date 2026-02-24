@extends('layouts.app')
@section('page')
    <div class="page fundis-wrapper">
        <div class="custom-header">
            <h2>Duplicate Manager</h2>
            <div class="actions">
                <a href="{{ url()->previous() }}" class="btn btn-custom mr-4" type="button">
                    <i class="fa-solid fa-arrow-left"></i> Back
                </a>
            </div>
        </div>
        <div class="u-fundis mt-5">
            @livewire('fundi-duplicates-table')
        </div>
    </div>
@endsection
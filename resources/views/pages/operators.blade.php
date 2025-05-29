@extends('layouts.app')
@section('page')
    <div class="page operators-wrapper">
        <div class="custom-header">
            <h2>Operators</h2>
            <div class="actions">
                <button class="btn btn-custom mr-4" type="button" data-toggle="modal" data-target="#addOperatorForm">
                    <i class="fa-solid fa-add"></i>Create New</button>
            </div>
        </div>

        @livewire('operators-table')

        <!-- Add Operator Modal -->
        @include('components.modals.add-operator-modal')

    </div>
@endsection

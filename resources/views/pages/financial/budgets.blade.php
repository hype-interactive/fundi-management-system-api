@extends('layouts.app')
@section('page')
    <div class="page budgets-wrapper">
        <div class="custom-header">
            <h2>Budget Allocations</h2>
            <div class="actions">
                <div class="dropdown">
                    <i class="fa-solid fa-bars icon" data-toggle="dropdown" aria-expanded="false"></i>
                    <div class="dropdown-menu">
                        <a href="{{ route('financials') }}" class="" type="button">
                            <i class="fa-solid fa-arrow-left mr-4"></i>Back to Dashboard
                        </a>
                        <div class="dropdown-divider"></div>
                        <a class="cursor-pointer" type="button" data-toggle="modal" data-target="#createBudgetModal">
                            <i class="fa-solid fa-plus mr-4"></i>New Budget
                        </a>
                        <a class="cursor-pointer" type="button" id="exportBudgets">
                            <i class="fa-solid fa-download mr-4"></i>Export
                        </a>
                    </div>
                </div>
            </div>
        </div>

        @livewire('budget-allocations-table')

        @include('components.modals.create-budget-modal')
        @include('components.modals.edit-budget-modal')
    </div>
@endsection

@extends('layouts.app')
@section('page')
    <div class="page financials-wrapper">
        <div class="custom-header">
            <h2>Financial Management</h2>
            <div class="actions">
                <div class="dropdown">
                    <i class="fa-solid fa-bars icon" data-toggle="dropdown" aria-expanded="false"></i>
                    <div class="dropdown-menu">
                        <a href="{{ route('financial.budgets') }}" class="" type="button">
                            <i class="fa-solid fa-wallet mr-4"></i>Budget Allocations
                        </a>
                        <a href="{{ route('financial.disbursements') }}" class="" type="button">
                            <i class="fa-solid fa-money-bill-transfer mr-4"></i>Disbursements
                        </a>
                        <a href="{{ route('financial.transactions') }}" class="" type="button">
                            <i class="fa-solid fa-receipt mr-4"></i>Transactions
                        </a>
                        <a href="{{ route('financial.reports') }}" class="" type="button">
                            <i class="fa-solid fa-chart-bar mr-4"></i>Reports
                        </a>
                        <div class="dropdown-divider"></div>
                        <a class="cursor-pointer" type="button" data-toggle="modal" data-target="#createBudgetModal">
                            <i class="fa-solid fa-plus mr-4"></i>New Budget
                        </a>
                        <a class="cursor-pointer" type="button" data-toggle="modal" data-target="#createDisbursementModal">
                            <i class="fa-solid fa-paper-plane mr-4"></i>New Disbursement
                        </a>
                    </div>
                </div>
            </div>
        </div>

        @livewire('financial-dashboard')

        <!-- Modals -->
        @include('components.modals.create-budget-modal')
        @include('components.modals.create-disbursement-modal')
    </div>
@endsection

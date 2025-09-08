@extends('layouts.app')
@section('page')
    <div class="page transactions-wrapper">
        <div class="custom-header">
            <h2>Transactions</h2>
            <div class="actions">
                <div class="dropdown">
                    <i class="fa-solid fa-bars icon" data-toggle="dropdown" aria-expanded="false"></i>
                    <div class="dropdown-menu">
                        <a href="{{ route('financials') }}" class="" type="button">
                            <i class="fa-solid fa-arrow-left mr-4"></i>Back to Dashboard
                        </a>
                        <div class="dropdown-divider"></div>
                        <a class="cursor-pointer" type="button" id="exportTransactions">
                            <i class="fa-solid fa-download mr-4"></i>Export
                        </a>
                        <a class="cursor-pointer" type="button" id="retryAllFailed">
                            <i class="fa-solid fa-redo mr-4"></i>Retry All Failed
                        </a>
                    </div>
                </div>
            </div>
        </div>

        @livewire('transactions-table')
    </div>
@endsection

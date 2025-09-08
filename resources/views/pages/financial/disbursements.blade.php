@extends('layouts.app')
@section('page')
    <div class="page disbursements-wrapper">
        <div class="custom-header">
            <h2>Disbursement Batches</h2>
            <div class="actions">
                <div class="dropdown">
                    <i class="fa-solid fa-bars icon" data-toggle="dropdown" aria-expanded="false"></i>
                    <div class="dropdown-menu">
                        <a href="{{ route('financials') }}" class="" type="button">
                            <i class="fa-solid fa-arrow-left mr-4"></i>Back to Dashboard
                        </a>
                        <div class="dropdown-divider"></div>
                        <a class="cursor-pointer" type="button" data-toggle="modal" data-target="#createDisbursementModal">
                            <i class="fa-solid fa-plus mr-4"></i>New Disbursement
                        </a>
                        <a class="cursor-pointer" type="button" id="exportDisbursements">
                            <i class="fa-solid fa-download mr-4"></i>Export
                        </a>
                    </div>
                </div>
            </div>
        </div>

        @livewire('disbursement-batches-table')

        @include('components.modals.create-disbursement-modal')
    </div>
@endsection

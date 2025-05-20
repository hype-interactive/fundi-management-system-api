@extends('layouts.app')
@section('page')
    <div class="page fundis-wrapper">
        <div class="custom-header">
            <h2>Mafundi</h2>
            <div class="actions">
                <div class="dropdown">
                    <i class="fa-solid fa-bars icon" data-toggle="dropdown" aria-expanded="false"></i>
                    <div class="dropdown-menu">
                        {{-- <a href="{{ route('special-clients') }}" class="" type="button"> <i
                                class="fa-solid fa-people-group mr-4"></i>Special Clients </a>
                        <a href="{{ route('unapproved') }}" class="" type="button"> <i
                                class="fa-solid fa-person-circle-xmark mr-4"></i>Unapproved </a> --}}
                        <a class="" type="button" data-toggle="modal" data-target="#addFundiForm"> <i
                                class="fa-solid fa-plus mr-4"></i>Add Fundi</a>
                        <a class="" type="button" data-toggle="modal" data-target="#bulk"><i
                                class="fa-solid fa-cloud-arrow-up mr-4"></i>Add Bulk</a>
                        <a class="cursor-pointer" type="button" id="fundis-pdf" onclick="Livewire.dispatch('downloadFundiPDF')">
                            <i class="fa-solid fa-cloud-arrow-down mr-4"></i>
                            Print
                        </a>
                    </div>
                </div>
            </div>
        </div>

        @livewire('fundis-table')

        <!-- Add Fundi Modal -->
        @include('components.modals.add-fundi-modal')

        <!-- Bulk Upload Modal -->
        @include('components.modals.bulk-fundi-upload-modal')

    </div>
@endsection

@extends('layouts.app')
@section('page')
    <div class="page fundis-wrapper">
        <div class="custom-header">
            <h2>Mafundi</h2>
            <div class="actions">
                <div class="dropdown">
                    <i class="fa-solid fa-bars icon" data-toggle="dropdown" aria-expanded="false"></i>
                    <div class="dropdown-menu">
                        <a href="{{ route('unapproved-fundis') }}" class="" type="button"> <i
                                class="fa-solid fa-person-circle-xmark mr-4"></i>Unapproved </a>
                            <a type="button" href="{{ route('fundis.duplicates') }}">
                                <i class="fa-solid fa-clone mr-4"></i>
                                Duplicate Manager
                            </a>
                            <a class="cursor-pointer" type="button" data-toggle="modal" data-target="#addFundiForm"> <i
                                    class="fa-solid fa-plus mr-4"></i>Add Fundi</a>
                            {{-- <a class="cursor-pointer" type="button" data-toggle="modal" data-target="#bulk"><i
                                    class="fa-solid fa-cloud-arrow-up mr-4"></i>Add Bulk</a> --}}
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

@extends('layouts.app')
@section('page')
    <div class="page client-wrapper">
        <div class="row">
            <div class="col-md-8">
                <div class="custom-header">
                    <h2>{{ $fundi->fname }} {{ $fundi->lname }}</h2>
                    <a href="{{ route('fundis') }}" class="btn btn-custom"><i class="fa-solid fa-arrow-left"></i> Back</a>
                </div>
                {{-- <div class="client-forms">
                    <nav>
                        <div class="nav nav-tabs-custom " id="nav-tab" role="tablist">
                            <button class="nav-link active " id="nav-home-tab" data-toggle="tab" data-target="#nav-home"
                                type="button" role="tab" aria-controls="nav-home" aria-selected="true">Client's
                                Information</button>
                            <button class="nav-link" id="nav-profile-tab" data-toggle="tab" data-target="#nav-profile"
                                type="button" role="tab" aria-controls="nav-profile" aria-selected="false">Payment &
                                Bill</button>
                        </div>
                    </nav>
                    <div class="tab-content" id="nav-tabContent">
                        <div class="tab-pane fade show active" id="nav-home" role="tabpanel"
                            aria-labelledby="nav-home-tab">
                            @livewire('client-form', ['client' => $client])
                        </div>
                        <div class="tab-pane fade payment-form" id="nav-profile" role="tabpanel"
                            aria-labelledby="nav-profile-tab">
                            @livewire('client-payment-form', ['client' => $client])
                        </div>
                    </div>
                </div> --}}
            </div>
            {{-- <div class="col-md-4">
                @livewire('client-profile', ['client' => $client])
            </div> --}}
        </div>
    </div>
@endsection

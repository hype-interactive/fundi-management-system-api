@extends('layouts.app')
@section('page')
    <div class="page fundi-wrapper">
        <div class="row">
            <div class="col-md-8">
                <div class="custom-header">
                    <h2>{{ $fundi->fname }} {{ $fundi->mname }} {{ $fundi->lname }}</h2>
                    <a href="{{ url()->previous() }}" class="btn btn-custom"><i class="fa-solid fa-arrow-left"></i> Back</a>
                </div>
                <div class="fundi-forms">
                    <nav>
                        <div class="nav nav-tabs-custom " id="nav-tab" role="tablist">
                            <button class="nav-link active " id="nav-home-tab" data-toggle="tab" data-target="#nav-home"
                                type="button" role="tab" aria-controls="nav-home" aria-selected="true">Fundi's
                                Information</button>
                            <button class="nav-link" id="nav-profile-tab" data-toggle="tab" data-target="#nav-profile"
                                type="button" role="tab" aria-controls="nav-profile" aria-selected="false">Transactions
                                &
                                Site Visits</button>
                        </div>
                    </nav>
                    <div class="tab-content" id="nav-tabContent">
                        <div class="tab-pane fade show active" id="nav-home" role="tabpanel"
                            aria-labelledby="nav-home-tab">
                            @livewire('fundi-form', ['fundi' => $fundi])
                        </div>
                        <div class="tab-pane fade transactions-form" id="nav-profile" role="tabpanel"
                            aria-labelledby="nav-profile-tab">
                            @livewire('fundi-transaction-form', ['fundi' => $fundi])
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                @livewire('fundi-profile', ['fundi' => $fundi])
            </div>
        </div>
    </div>
    <script>
        window.addEventListener('fundiLegacyMatch', event => {
            alert('This Fundi has a legacy match in the system');
        })
    </script>
    <script>
        // Handle collapse icon rotation
        document.addEventListener('DOMContentLoaded', function() {
            const collapseHeaders = document.querySelectorAll('[data-toggle="collapse"]');

            collapseHeaders.forEach(header => {
                const target = document.querySelector(header.getAttribute('data-target'));

                if (target) {
                    target.addEventListener('show.bs.collapse', function() {
                        header.setAttribute('aria-expanded', 'true');
                    });

                    target.addEventListener('hide.bs.collapse', function() {
                        header.setAttribute('aria-expanded', 'false');
                    });
                }
            });
        });
    </script>
@endsection

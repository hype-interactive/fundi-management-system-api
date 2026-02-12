@extends('layouts.app')
@section('page')
    <div class="page operator-wrapper">
        <div class="row">
            <div class="col-md-8">
                <div class="custom-header">
                    <h2>{{ $operator->full_name }}</h2>
                    <a href="{{ url()->previous() }}" class="btn btn-custom"><i class="fa-solid fa-arrow-left"></i> Back</a>
                </div>
                <div class="operator-forms">
                    <nav>
                        <div class="nav nav-tabs-custom" id="nav-tab" role="tablist">
                            <button class="nav-link active" id="nav-home-tab" data-toggle="tab" data-target="#nav-mafundi"
                                type="button" role="tab" aria-controls="nav-home" aria-selected="true">Mafundi
                            </button>
                            <button class="nav-link " id="nav-home-tab" data-toggle="tab" data-target="#nav-home"
                                type="button" role="tab" aria-controls="nav-home" aria-selected="true">Site Visits</button>
                            <button class="nav-link" id="nav-profile-tab" data-toggle="tab" data-target="#nav-profile"
                                type="button" role="tab" aria-controls="nav-profile" aria-selected="false">Operator's
                                Information
                            </button>
                            {{-- <button class="nav-link" id="nav-incentives-tab" data-toggle="tab" data-target="#nav-incentives"
                                type="button" role="tab" aria-controls="nav-incentives"
                                aria-selected="false">Incentives & Performance</button> --}}
                        </div>
                    </nav>
                    <div class="tab-content" id="nav-tabContent">
                        <div class="tab-pane fade show active" id="nav-mafundi" role="tabpanel"
                            aria-labelledby="nav-home-tab">
                            @livewire('operator-mafundi', ['operator' => $operator])
                        </div>
                        <div class="tab-pane fade show" id="nav-home" role="tabpanel"
                            aria-labelledby="nav-home-tab">
                            @livewire('operator-site-visits', ['operator' => $operator])
                        </div>
                        <div class="tab-pane fade activities-form" id="nav-profile" role="tabpanel"
                            aria-labelledby="nav-profile-tab">
                            @livewire('operator-form', ['operator' => $operator])
                        </div>
                        <div class="tab-pane fade incentives-form" id="nav-incentives" role="tabpanel"
                            aria-labelledby="nav-incentives-tab">
                            @livewire('operator-incentives-form', ['operator' => $operator])
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                @livewire('operator-profile', ['operator' => $operator])
            </div>
        </div>
    </div>
    <script>
        window.addEventListener('operatorStatusChanged', event => {
            location.reload();
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

@extends('layouts.app')
@section('page')
    <div class="page site-visit-wrapper">
        <div class="row">
            <div class="col-md-8">
                <div class="custom-header site-visit-header">
                    <div class="header-content">
                        <h2>{{ $siteVisit->site_name }}</h2>
                        <div class="header-meta">
                            <span class="visit-date">
                                <i class="fa-solid fa-calendar mr-2"></i>
                                {{ $siteVisit->visit_date->format('M d, Y') }}
                            </span>
                            @if ($siteVisit->status === 'rejected')
                                <span class="badge bg-danger ms-2">Rejected</span>
                            @elseif ($siteVisit->status === 'approved')
                                <span class="badge bg-success ms-2">Approved</span>
                            @else
                                <span class="badge bg-warning ms-2">Pending</span>
                            @endif
                        </div>
                    </div>
                    <a href="{{ route('site-visits') }}" class="btn btn-custom">
                        <i class="fa-solid fa-arrow-left"></i> Back
                    </a>
                </div>

                @livewire('site-visit.tabs', ['siteVisit' => $siteVisit])
            </div>

            <div class="col-md-4">
                @livewire('site-visit.sidebar', ['siteVisit' => $siteVisit])
            </div>
        </div>
    </div>

    <script>
        // Handle tab switching
        document.addEventListener('DOMContentLoaded', function() {
            const tabButtons = document.querySelectorAll('[data-toggle="tab"]');

            tabButtons.forEach(button => {
                button.addEventListener('click', function(e) {
                    e.preventDefault();

                    // Remove active class from all tabs and content
                    document.querySelectorAll('.nav-link').forEach(link => link.classList.remove(
                        'active'));
                    document.querySelectorAll('.tab-pane').forEach(pane => {
                        pane.classList.remove('show', 'active');
                    });

                    // Add active class to clicked tab
                    this.classList.add('active');

                    // Show corresponding content
                    const targetId = this.getAttribute('data-target');
                    const targetPane = document.querySelector(targetId);
                    if (targetPane) {
                        targetPane.classList.add('show', 'active');
                    }
                });
            });
        });

        // Action functions
        function approveVisit(visitId) {
            if (confirm('Are you sure you want to approve this site visit?')) {
                // Add your approval logic here
                console.log('Approving visit:', visitId);
            }
        }

        function rejectVisit(visitId) {
            const reason = prompt('Please provide a reason for rejection:');
            if (reason) {
                // Add your rejection logic here
                console.log('Rejecting visit:', visitId, 'Reason:', reason);
            }
        }

        function exportVisit(visitId) {
            // Add your export logic here
            console.log('Exporting visit:', visitId);
        }
    </script>
@endsection

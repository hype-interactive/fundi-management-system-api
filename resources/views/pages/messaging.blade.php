@extends('layouts.app')
@section('page')
    <div class="page sms-campaigns-wrapper">
        <div class="custom-header">
            <h2>SMS Campaigns</h2>
            <div class="actions">
                <button onclick="showQuickHelp()" class="btn btn-outline-info btn-sm">
                    <i class="fa-solid fa-question-circle me-1"></i>
                    Quick Help
                </button>
            </div>
        </div>
        <div class="messaging-tabs">
            <nav>
                <div class="nav nav-tabs-custom" id="nav-tab" role="tablist">
                    <button class="nav-link active" id="nav-sms-campaigns-tab" data-toggle="tab"
                        data-target="#nav-sms-campaigns" type="button" role="tab" aria-controls="nav-sms-campaigns"
                        aria-selected="true">SMS Campaigns</button>
                    <button class="nav-link" id="nav-sms-templates-tab" data-toggle="tab"
                        data-target="#nav-sms-templates" type="button" role="tab" aria-controls="nav-sms-templates"
                        aria-selected="false">SMS Templates</button>
                </div>
            </nav>
            <div class="tab-content" id="nav-tabContent">
                <div class="tab-pane fade show active" id="nav-sms-campaigns" role="tabpanel"
                    aria-labelledby="nav-sms-campaigns-tab">
                    @livewire('sms-campaign-list')
                </div>
                <div class="tab-pane fade" id="nav-sms-templates" role="tabpanel"
                    aria-labelledby="nav-sms-templates-tab">
                    @livewire('sms-template-list')
                </div>
            </div>
        </div>

        <!-- Quick Help Modal -->
        @include('components.modals.quick-help-modal')
    </div>

    <script>
        function showQuickHelp() {
            const modal = new bootstrap.Modal(document.getElementById('quickHelpModal'));
            modal.show();
        }
    </script>
@endsection

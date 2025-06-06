<div wire:poll.keep-alive>
    <!-- Summary Metrics -->
    <div class="row mb-4 platform-summary">

        <!-- Fundi Registration Summary -->
        <?php
        $amount = $total_fundis;
        $status = $fundis_month_status;
        $difference = $fundis_month_diff;
        $type = 'Total Mafundi';
        ?>
        <div class="col-md-6 mb-3">
            @include('components.collection_tile')
        </div>

        <!-- Site Visits Summary -->
        <?php
        $amount = $total_visits;
        $status = $visits_month_status;
        $difference = $visits_month_diff;
        $type = 'Total Site Visits';
        ?>
        <div class="col-md-6 mb-3">
            @include('components.collection_tile')
        </div>

        <!-- Disbursements Summary -->
        <?php
        $amount = $total_disbursements;
        $status = $disbursements_month_status;
        $difference = $disbursements_month_diff;
        $type = 'Disburse (TZS)';
        ?>
        <div class="col-md-6 mb-3">
            @include('components.collection_tile')
        </div>

        <!-- Performance Summary -->
        <?php
        $amount = $fundi_approval_rate;
        $status = $performance_status;
        $difference = 0; // Performance metrics don't need difference
        $type = 'Apprv. Rate (%)';
        ?>
        <div class="col-md-6 mb-3">
            @include('components.collection_tile')
        </div>
    </div>
</div>

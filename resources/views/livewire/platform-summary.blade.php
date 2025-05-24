<div wire:poll.keep-alive>
    <!-- Filter Section -->
    {{-- <div class="row mb-4">
        <div class="col-md-4">
            <label for="district-filter" class="form-label">Filter by District</label>
            <select wire:model="selected_district" id="district-filter" class="form-select">
                <option value="">All Districts</option>
                @foreach($districts as $district)
                    <option value="{{ $district->id }}">{{ $district->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-8">
            <div class="row">
                <div class="col-md-3">
                    <div class="card bg-primary text-white">
                        <div class="card-body text-center">
                            <h3 class="card-title">{{ number_format($total_fundis) }}</h3>
                            <p class="card-text">Total Fundis</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-success text-white">
                        <div class="card-body text-center">
                            <h3 class="card-title">{{ number_format($total_operators) }}</h3>
                            <p class="card-text">Active Operators</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-warning text-white">
                        <div class="card-body text-center">
                            <h3 class="card-title">{{ number_format($pending_fundis) }}</h3>
                            <p class="card-text">Pending Fundis</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-info text-white">
                        <div class="card-body text-center">
                            <h3 class="card-title">{{ number_format($pending_site_visits) }}</h3>
                            <p class="card-text">Pending Visits</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div> --}}

    <!-- Fundi Registration Metrics -->
    <div class="row mb-4 platform-summary">
        <div class="col-12">
            <h4 class="mb-3">
                <i class="fas fa-users text-primary"></i> Fundi Registration Metrics
            </h4>
        </div>

        <?php
        $amount = $fundis_today;
        $status = $fundis_today_status;
        $difference = $fundis_today_diff;
        $type = 'TODAY';
        ?>
        <div class="col-md-6 mb-3">
            @include('components.collection_tile')
        </div>

        <?php
        $amount = $fundis_week;
        $status = $fundis_week_status;
        $difference = $fundis_week_diff;
        $type = 'WEEK';
        ?>
        <div class="col-md-6 mb-3">
            @include('components.collection_tile')
        </div>

        <?php
        $amount = $fundis_month;
        $status = $fundis_month_status;
        $difference = $fundis_month_diff;
        $type = 'MONTH';
        ?>
        <div class="col-md-6 mb-3">
            @include('components.collection_tile')
        </div>

        <?php
        $amount = $fundis_year;
        $status = $fundis_year_status;
        $difference = $fundis_year_diff;
        $type = 'YEAR';
        ?>
        <div class="col-md-6 mb-3">
            @include('components.collection_tile')
        </div>
    </div>

    <!-- Site Visit Metrics -->
    <div class="row mb-4 platform-summary">
        <div class="col-12">
            <h4 class="mb-3">
                <i class="fas fa-map-marker-alt text-success"></i> Site Visit Metrics
            </h4>
        </div>

        <?php
        $amount = $visits_today;
        $status = $visits_today_status;
        $difference = $visits_today_diff;
        $type = 'TODAY - Visits';
        ?>
        <div class="col-md-6 mb-3">
            @include('components.collection_tile')
        </div>

        <?php
        $amount = $visits_week;
        $status = $visits_week_status;
        $difference = $visits_week_diff;
        $type = 'WEEK - Visits';
        ?>
        <div class="col-md-6 mb-3">
            @include('components.collection_tile')
        </div>

        <?php
        $amount = $visits_month;
        $status = $visits_month_status;
        $difference = $visits_month_diff;
        $type = 'MONTH - Visits';
        ?>
        <div class="col-md-6 mb-3">
            @include('components.collection_tile')
        </div>

        <?php
        $amount = $visits_year;
        $status = $visits_year_status;
        $difference = $visits_year_diff;
        $type = 'YEAR - Visits';
        ?>
        <div class="col-md-6 mb-3">
            @include('components.collection_tile')
        </div>
    </div>

    <!-- Financial Metrics -->
    <div class="row mb-4 platform-summary">
        <div class="col-12">
            <h4 class="mb-3">
                <i class="fas fa-money-bill-wave text-warning"></i> Disbursement Metrics
            </h4>
        </div>

        <?php
        $amount = $disbursements_today;
        $status = $disbursements_today_status;
        $difference = $disbursements_today_diff;
        $type = 'TODAY - TZS';
        ?>
        <div class="col-md-6 mb-3">
            @include('components.collection_tile')
        </div>

        <?php
        $amount = $disbursements_week;
        $status = $disbursements_week_status;
        $difference = $disbursements_week_diff;
        $type = 'WEEK - TZS';
        ?>
        <div class="col-md-6 mb-3">
            @include('components.collection_tile')
        </div>

        <?php
        $amount = $disbursements_month;
        $status = $disbursements_month_status;
        $difference = $disbursements_month_diff;
        $type = 'MONTH - TZS';
        ?>
        <div class="col-md-6 mb-3">
            @include('components.collection_tile')
        </div>

        <?php
        $amount = $disbursements_year;
        $status = $disbursements_year_status;
        $difference = $disbursements_year_diff;
        $type = 'YEAR - TZS';
        ?>
        <div class="col-md-6 mb-3">
            @include('components.collection_tile')
        </div>
    </div>

    <!-- Performance Metrics -->
    <div class="row mb-4 platform-summary">
        <div class="col-12">
            <h4 class="mb-3">
                <i class="fas fa-chart-line text-info"></i> Performance Metrics
            </h4>
        </div>

        <div class="col-md-6 mb-3">
            <div class="collection-tile-wrapper">
                <div class="tile">
                    <div class="row">
                        <div class="col-sm-8">
                            <div class="main-text">
                                {{ $fundi_approval_rate }}%
                                @if ($fundi_approval_rate >= 80)
                                    <i class="fa-solid fa-arrow-up-long up"></i>
                                @else
                                    <i class="fa-solid fa-arrow-down-long down"></i>
                                @endif
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="text-semi-bold">Fundi Approval Rate</div>
                            <div class="separator"></div>
                            <div class="text-semi-bold">
                                {{ $active_fundis }}/{{ $total_fundis }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-6 mb-3">
            <div class="collection-tile-wrapper">
                <div class="tile">
                    <div class="row">
                        <div class="col-sm-8">
                            <div class="main-text">
                                {{ $visit_approval_rate }}%
                                @if ($visit_approval_rate >= 80)
                                    <i class="fa-solid fa-arrow-up-long up"></i>
                                @else
                                    <i class="fa-solid fa-arrow-down-long down"></i>
                                @endif
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="text-semi-bold">Visit Approval Rate</div>
                            <div class="separator"></div>
                            <div class="text-semi-bold">
                                This Month
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Action Items -->
    <div class="row d-none">
        <div class="col-12">
            <h4 class="mb-3">
                <i class="fas fa-tasks text-danger"></i> Action Items
            </h4>
        </div>

        @if($pending_fundis > 0)
        <div class="col-md-4 mb-3">
            <div class="card border-warning">
                <div class="card-body">
                    <h5 class="card-title text-warning">
                        <i class="fas fa-exclamation-triangle"></i> Pending Fundis
                    </h5>
                    <p class="card-text">
                        {{ number_format($pending_fundis) }} fundis awaiting approval
                    </p>
                    {{-- <a href="{{ route('fundis.pending') }}" class="btn btn-warning btn-sm">
                        Review Now
                    </a> --}}
                </div>
            </div>
        </div>
        @endif

        @if($pending_site_visits > 0)
        <div class="col-md-4 mb-3">
            <div class="card border-info">
                <div class="card-body">
                    <h5 class="card-title text-info">
                        <i class="fas fa-map-marker-alt"></i> Pending Site Visits
                    </h5>
                    <p class="card-text">
                        {{ number_format($pending_site_visits) }} site visits awaiting review
                    </p>
                    {{-- <a href="{{ route('site-visits.pending') }}" class="btn btn-info btn-sm">
                        Review Now
                    </a> --}}
                </div>
            </div>
        </div>
        @endif

        @if($fundi_approval_rate < 80)
        <div class="col-md-4 mb-3">
            <div class="card border-danger">
                <div class="card-body">
                    <h5 class="card-title text-danger">
                        <i class="fas fa-chart-line"></i> Low Approval Rate
                    </h5>
                    <p class="card-text">
                        Fundi approval rate is {{ $fundi_approval_rate }}% (below 80%)
                    </p>
                    {{-- <a href="{{ route('reports.approval-analysis') }}" class="btn btn-danger btn-sm">
                        Analyze
                    </a> --}}
                </div>
            </div>
        </div>
        @endif
    </div>
</div>

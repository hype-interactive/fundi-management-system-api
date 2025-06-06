<!-- Chart Filters Component -->
<div class="filter">
    <div class="filter-header">
        <h6>
            <i class="fas fa-filter me-2"></i>
            Filters & Controls
        </h6>
    </div>
    <hr>

    <!-- Time Range Filter -->
    <div class="filter-group mb-3">
        <label for="timeRange">
            <i class="fas fa-clock me-1"></i>
            Time Period
        </label>
        <select wire:model.live="selectedTimeRange" id="timeRange" class="form-control" disabled>
            <option value="3_months">Last 3 Months</option>
            <option value="6_months">Last 6 Months</option>
            <option value="12_months">Last 12 Months</option>
            <option value="current_year">Current Year</option>
            <option value="last_year">Last Year</option>
            <option value="custom">Custom Range</option>
        </select>
    </div>

    <!-- Custom Date Range (shown when custom is selected) -->
    @if ($selectedTimeRange === 'custom')
        <div class="filter-group mb-3">
            <label for="dateFrom">
                <i class="fas fa-calendar-alt me-1"></i>
                From Date
            </label>
            <input type="date" wire:model.live="dateFrom" id="dateFrom" class="form-control">
        </div>

        <div class="filter-group mb-3">
            <label for="dateTo">
                <i class="fas fa-calendar-alt me-1"></i>
                To Date
            </label>
            <input type="date" wire:model.live="dateTo" id="dateTo" class="form-control">
        </div>
    @endif

    <!-- Chart Type Filter -->
    <div class="filter-group mb-3">
        <label for="chartType">
            <i class="fas fa-chart-area me-1"></i>
            Display Charts
        </label>
        <select wire:model.live="selectedChartType" id="chartType" class="form-control" disabled>
            <option value="both">Both Charts</option>
            <option value="fundi">Fundi Only</option>
            <option value="visits">Visits Only</option>
        </select>
    </div>

    <!-- Metric Type Filter -->
    <div class="filter-group mb-3">
        <label for="metricType">
            <i class="fas fa-calculator me-1"></i>
            Metric Type
        </label>
        <select wire:model.live="selectedMetric" id="metricType" class="form-control" disabled>
            <option value="count">Monthly Count</option>
            <option value="cumulative">Cumulative</option>
        </select>
    </div>

    <!-- Reset Filters -->
    <div class="filter-actions mt-4">
        {{-- <button wire:click="resetFilters" class="reset-btn"> --}}
        <button onclick="location.reload()" class="reset-btn">
            <i class="fas fa-undo me-2"></i>
            Reset All Filters
        </button>
    </div>
</div>

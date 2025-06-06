<!-- Charts Display Component -->
<div class="charts-container">
    <div class="row">
        @if($selectedChartType === 'both' || $selectedChartType === 'fundi')
        <div class="col-md-{{ $selectedChartType === 'both' ? '12' : '12' }} mb-4">
            <div class="chart-card">
                <div class="chart-header">
                    <h3 class="chart-title">
                        <i class="fas fa-user-plus chart-icon"></i>
                        Fundi Registrations
                    </h3>
                </div>
                <div class="chart-content">
                    <canvas id="fundiChart"></canvas>
                </div>
                <div class="chart-footer">
                    <div class="chart-stats">
                        <span class="stat-item">
                            <span class="stat-label">Total:</span>
                            <span class="stat-value">{{ array_sum($fundiData['data'] ?? []) }}</span>
                        </span>
                        <span class="stat-item">
                            <span class="stat-label">Average:</span>
                            <span class="stat-value">{{ count($fundiData['data'] ?? []) > 0 ? round(array_sum($fundiData['data']) / count($fundiData['data']), 1) : 0 }}</span>
                        </span>
                    </div>
                </div>
            </div>
        </div>
        @endif

        @if($selectedChartType === 'both' || $selectedChartType === 'visits')
        <div class="col-md-{{ $selectedChartType === 'both' ? '12' : '12' }} mb-4">
            <div class="chart-card">
                <div class="chart-header">
                    <h3 class="chart-title">
                        <i class="fas fa-chart-bar chart-icon"></i>
                        Site Visits
                    </h3>
                </div>
                <div class="chart-content">
                    <canvas id="siteVisitChart"></canvas>
                </div>
                <div class="chart-footer">
                    <div class="chart-stats">
                        <span class="stat-item">
                            <span class="stat-label">Total:</span>
                            <span class="stat-value">{{ array_sum($siteVisitData['data'] ?? []) }}</span>
                        </span>
                        <span class="stat-item">
                            <span class="stat-label">Peak:</span>
                            <span class="stat-value">{{ count($siteVisitData['data'] ?? []) > 0 ? max($siteVisitData['data']) : 0 }}</span>
                        </span>
                    </div>
                </div>
            </div>
        </div>
        @endif
    </div>
</div>

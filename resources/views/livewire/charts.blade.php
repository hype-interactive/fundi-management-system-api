<div>
    <div class="row">
        <!-- Charts Section -->
        <div class="col-md-9">
            <div class="header mb-4">
                <h2>Analytics</h2>
                <p>Track your platform's performance and growth metrics</p>
            </div>

            @include('components.charts.charts-display')
        </div>

        <!-- Filters & Summary Section -->
        <div class="col-md-3">
            <div class="summary dark-bg">
                @include('components.charts.chart-summary')
                @include('components.charts.chart-filters')
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('livewire:init', function() {
            let fundiChart = null;
            let siteVisitChart = null;

            // Initialize charts
            function initializeCharts() {
                const fundiCtx = document.getElementById('fundiChart');
                const siteVisitCtx = document.getElementById('siteVisitChart');

                if (fundiCtx && siteVisitCtx) {
                    createCharts();
                }
            }

            function createCharts() {
                // Destroy existing charts
                if (fundiChart) fundiChart.destroy();
                if (siteVisitChart) siteVisitChart.destroy();

                // Fundi Registration Chart
                const fundiCtx = document.getElementById('fundiChart').getContext('2d');
                fundiChart = new Chart(fundiCtx, {
                    type: 'line',
                    data: {
                        labels: @json($fundiData['labels'] ?? []),
                        datasets: [{
                            label: 'Fundi Registrations',
                            data: @json($fundiData['data'] ?? []),
                            borderColor: 'rgb(59, 130, 246)',
                            backgroundColor: 'rgba(59, 130, 246, 0.1)',
                            borderWidth: 3,
                            fill: true,
                            tension: 0.4,
                            pointBackgroundColor: 'rgb(59, 130, 246)',
                            pointBorderColor: '#fff',
                            pointBorderWidth: 2,
                            pointRadius: 6,
                            pointHoverRadius: 8
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        interaction: {
                            intersect: false,
                            mode: 'index'
                        },
                        scales: {
                            y: {
                                beginAtZero: true,
                                ticks: {
                                    stepSize: 1,
                                    color: '#6b7280'
                                },
                                grid: {
                                    color: 'rgba(107, 114, 128, 0.1)'
                                }
                            },
                            x: {
                                ticks: {
                                    color: '#6b7280'
                                },
                                grid: {
                                    display: false
                                }
                            }
                        },
                        plugins: {
                            legend: {
                                display: false
                            },
                            tooltip: {
                                backgroundColor: 'rgba(0, 0, 0, 0.8)',
                                titleColor: '#fff',
                                bodyColor: '#fff',
                                borderColor: 'rgb(59, 130, 246)',
                                borderWidth: 1
                            }
                        }
                    }
                });

                // Site Visit Chart
                const siteVisitCtx = document.getElementById('siteVisitChart').getContext('2d');
                siteVisitChart = new Chart(siteVisitCtx, {
                    type: 'bar',
                    data: {
                        labels: @json($siteVisitData['labels'] ?? []),
                        datasets: [{
                            label: 'Site Visits',
                            data: @json($siteVisitData['data'] ?? []),
                            backgroundColor: 'rgba(34, 197, 94, 0.8)',
                            borderColor: 'rgb(34, 197, 94)',
                            borderWidth: 2,
                            borderRadius: 6,
                            borderSkipped: false,
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        interaction: {
                            intersect: false,
                            mode: 'index'
                        },
                        scales: {
                            y: {
                                beginAtZero: true,
                                ticks: {
                                    stepSize: 1,
                                    color: '#6b7280'
                                },
                                grid: {
                                    color: 'rgba(107, 114, 128, 0.1)'
                                }
                            },
                            x: {
                                ticks: {
                                    color: '#6b7280'
                                },
                                grid: {
                                    display: false
                                }
                            }
                        },
                        plugins: {
                            legend: {
                                display: false
                            },
                            tooltip: {
                                backgroundColor: 'rgba(0, 0, 0, 0.8)',
                                titleColor: '#fff',
                                bodyColor: '#fff',
                                borderColor: 'rgb(34, 197, 94)',
                                borderWidth: 1
                            }
                        }
                    }
                });
            }

            // Listen for chart updates
            Livewire.on('updateCharts', function(fundiData, siteVisitData) {
                if (fundiChart && siteVisitChart) {
                    // Update Fundi chart
                    fundiChart.data.labels = fundiData.labels;
                    fundiChart.data.datasets[0].data = fundiData.data;
                    fundiChart.update('active');

                    // Update Site Visit chart
                    siteVisitChart.data.labels = siteVisitData.labels;
                    siteVisitChart.data.datasets[0].data = siteVisitData.data;
                    siteVisitChart.update('active');
                }
            });

            // Initialize charts when DOM is ready
            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', initializeCharts);
            } else {
                initializeCharts();
            }
        });
    </script>
</div>

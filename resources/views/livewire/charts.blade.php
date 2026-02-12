<div>
    <!-- Charts Section -->
    <div class="header mb-4">
        <h2>Analytics</h2>
        <p>Track your platform's performance and growth metrics</p>
    </div>
    @include('components.charts.charts-display')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            let fundiChart = null;
            let siteVisitChart = null;

            // // Get initial data from Livewire
            // let fundiData = @this.fundiData || {
            //     labels: [],
            //     data: []
            // };
            // let siteVisitData = @this.siteVisitData || {
            //     labels: [],
            //     data: []
            // };

            // Initialize data
            let fundiData = {
                labels: @json($fundiData['labels'] ?? []),
                data: @json($fundiData['data'] ?? [])
            };

            let siteVisitData = {
                labels: @json($siteVisitData['labels'] ?? []),
                data: @json($siteVisitData['data'] ?? [])
            };

            function initializeCharts() {
                const fundiCtx = document.getElementById('fundiChart');
                const siteVisitCtx = document.getElementById('siteVisitChart');

                if (fundiCtx) {
                    createFundiChart();
                }

                if (siteVisitCtx) {
                    createSiteVisitChart();
                }
            }

            function createFundiChart() {
                const fundiCtx = document.getElementById('fundiChart');
                if (!fundiCtx) return;

                // Destroy existing chart
                if (fundiChart) {
                    fundiChart.destroy();
                    fundiChart = null;
                }

                fundiChart = new Chart(fundiCtx.getContext('2d'), {
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
            }

            function createSiteVisitChart() {
                const siteVisitCtx = document.getElementById('siteVisitChart');
                if (!siteVisitCtx) return;

                // Destroy existing chart
                if (siteVisitChart) {
                    siteVisitChart.destroy();
                    siteVisitChart = null;
                }

                siteVisitChart = new Chart(siteVisitCtx.getContext('2d'), {
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

            function updateCharts(newFundiData, newSiteVisitData) {
                // Update data
                fundiData = newFundiData || {
                    labels: [],
                    data: []
                };
                siteVisitData = newSiteVisitData || {
                    labels: [],
                    data: []
                };

                // Update charts if they exist
                if (fundiChart && document.getElementById('fundiChart')) {
                    fundiChart.data.labels = fundiData.labels || [];
                    fundiChart.data.datasets[0].data = fundiData.data || [];
                    fundiChart.update('active');
                } else if (document.getElementById('fundiChart')) {
                    createFundiChart();
                }

                if (siteVisitChart && document.getElementById('siteVisitChart')) {
                    siteVisitChart.data.labels = siteVisitData.labels || [];
                    siteVisitChart.data.datasets[0].data = siteVisitData.data || [];
                    siteVisitChart.update('active');
                } else if (document.getElementById('siteVisitChart')) {
                    createSiteVisitChart();
                }
            }

            // Initialize charts
            initializeCharts();

            // Listen for Livewire updates
            document.addEventListener('livewire:init', function() {
                Livewire.on('updateCharts', function(event) {
                    const [newFundiData, newSiteVisitData] = event;
                    updateCharts(newFundiData, newSiteVisitData);
                });
            });

            // Re-initialize charts when Livewire updates the DOM
            document.addEventListener('livewire:navigated', function() {
                setTimeout(initializeCharts, 100);
            });
        });
    </script>
</div>

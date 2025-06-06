<div>
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Fundi Registration Trends -->
        <div class="bg-white p-6 rounded-lg shadow">
            <h3 class="text-lg font-semibold mb-4">Monthly Fundi Registrations</h3>
            <div class="h-64">
                <canvas id="fundiChart"></canvas>
            </div>
        </div>

        <!-- Site Visit Trends -->
        <div class="bg-white p-6 rounded-lg shadow">
            <h3 class="text-lg font-semibold mb-4">Monthly Site Visits</h3>
            <div class="h-64">
                <canvas id="siteVisitChart"></canvas>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Fundi Registration Chart
            var fundiCtx = document.getElementById('fundiChart').getContext('2d');
            var fundiChart = new Chart(fundiCtx, {
                type: 'line',
                data: {
                    labels: {!! json_encode($fundiData['labels']) !!},
                    datasets: [{
                        label: 'Fundi Registrations',
                        data: {!! json_encode($fundiData['data']) !!},
                        borderColor: 'rgb(59, 130, 246)',
                        backgroundColor: 'rgba(59, 130, 246, 0.1)',
                        borderWidth: 2,
                        fill: true,
                        tension: 0.4
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                stepSize: 1
                            }
                        }
                    },
                    plugins: {
                        legend: {
                            display: false
                        }
                    }
                }
            });

            // Site Visit Chart
            var siteVisitCtx = document.getElementById('siteVisitChart').getContext('2d');
            var siteVisitChart = new Chart(siteVisitCtx, {
                type: 'bar',
                data: {
                    labels: {!! json_encode($siteVisitData['labels']) !!},
                    datasets: [{
                        label: 'Site Visits',
                        data: {!! json_encode($siteVisitData['data']) !!},
                        backgroundColor: 'rgba(34, 197, 94, 0.8)',
                        borderColor: 'rgb(34, 197, 94)',
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                stepSize: 1
                            }
                        }
                    },
                    plugins: {
                        legend: {
                            display: false
                        }
                    }
                }
            });
        });
    </script>
</div>

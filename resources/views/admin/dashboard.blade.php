<x-app-layout>
    <x-slot name="header">
        {{ __('Administrator Dashboard') }}
    </x-slot>

    @push('styles')
        <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="">
        <style>
            #admin-dashboard-map {
                height: 350px;
                border-radius: 0.75rem;
                border: 1px solid #dee2e6;
                z-index: 1;
            }
        </style>
    @endpush

    <!-- Status KPI Grid -->
    <div class="row g-3 mb-4">
        <div class="col-6 col-lg-3">
            <div class="card h-100 shadow-sm border-0 border-start border-primary border-4">
                <div class="card-body">
                    <div class="text-muted small text-uppercase fw-semibold">Total Reports</div>
                    <h2 class="fw-bold mb-0 text-dark mt-1" id="kpi-total">—</h2>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="card h-100 shadow-sm border-0 border-start border-secondary border-4">
                <div class="card-body">
                    <div class="text-muted small text-uppercase fw-semibold">New Reports</div>
                    <h2 class="fw-bold mb-0 text-dark mt-1" id="kpi-submitted">—</h2>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="card h-100 shadow-sm border-0 border-start border-info border-4">
                <div class="card-body">
                    <div class="text-muted small text-uppercase fw-semibold">In Investigation</div>
                    <h2 class="fw-bold mb-0 text-dark mt-1" id="kpi-in_progress">—</h2>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="card h-100 shadow-sm border-0 border-start border-success border-4">
                <div class="card-body">
                    <div class="text-muted small text-uppercase fw-semibold">Cases Resolved</div>
                    <h2 class="fw-bold mb-0 text-dark mt-1" id="kpi-resolved">—</h2>
                </div>
            </div>
        </div>
    </div>

    <!-- Active Details Grid -->
    <div class="row g-4 mb-4">
        <div class="col-lg-4">
            <div class="card h-100 shadow-sm border-0">
                <div class="card-header bg-white py-3 border-0">
                    <h6 class="mb-0 fw-bold text-dark"><i class="bi bi-gear-fill text-primary me-2"></i>System Controls</h6>
                </div>
                <div class="card-body">
                    <div class="list-group list-group-flush">
                        <div class="list-group-item d-flex justify-content-between align-items-center px-0">
                            <div>
                                <div class="fw-semibold small text-dark">Active Agencies</div>
                                <div class="text-muted small">Government branches listed</div>
                            </div>
                            <span class="badge bg-dark rounded-pill fs-7" id="control-agencies">—</span>
                        </div>
                        <div class="list-group-item d-flex justify-content-between align-items-center px-0">
                            <div>
                                <div class="fw-semibold small text-dark">Dispatched Cases</div>
                                <div class="text-muted small">Agencies actively assigned</div>
                            </div>
                            <span class="badge bg-primary rounded-pill fs-7" id="control-assignments">—</span>
                        </div>
                        <div class="list-group-item d-flex justify-content-between align-items-center px-0">
                            <div>
                                <div class="fw-semibold small text-dark">SMS Sent Alerts</div>
                                <div class="text-muted small">Delivered via TextBee gateway</div>
                            </div>
                            <span class="badge bg-success rounded-pill fs-7" id="control-sms-sent">—</span>
                        </div>
                        <div class="list-group-item d-flex justify-content-between align-items-center px-0">
                            <div>
                                <div class="fw-semibold small text-dark">Avg. Case Resolution Time</div>
                                <div class="text-muted small">Average hours to resolve incidents</div>
                            </div>
                            <span class="badge bg-warning text-dark rounded-pill fs-7" id="control-avg-resolution">— hrs</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-8">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-white py-3 border-0">
                    <h6 class="mb-0 fw-bold text-dark"><i class="bi bi-geo-alt-fill text-primary me-2"></i>Emergency Hot Spots Map</h6>
                </div>
                <div class="card-body p-2">
                    <div id="admin-dashboard-map"></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Analytics Section -->
    <div class="row g-4 mb-4">
        <!-- Weekly Trend Chart -->
        <div class="col-12 col-xl-5">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-header bg-white py-3 border-0">
                    <h6 class="mb-0 fw-bold text-dark"><i class="bi bi-graph-up text-primary me-2"></i>Weekly Incident Volume Trends</h6>
                </div>
                <div class="card-body">
                    <div style="position: relative; height: 250px;">
                        <canvas id="weeklyTrendsChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- Incident Categories Doughnut -->
        <div class="col-12 col-md-6 col-xl-3">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-header bg-white py-3 border-0">
                    <h6 class="mb-0 fw-bold text-dark"><i class="bi bi-pie-chart-fill text-primary me-2"></i>Category Distribution</h6>
                </div>
                <div class="card-body">
                    <div style="position: relative; height: 250px;">
                        <canvas id="categoryDistributionChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- Barangay Frequency Bar -->
        <div class="col-12 col-md-6 col-xl-4">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-header bg-white py-3 border-0">
                    <h6 class="mb-0 fw-bold text-dark"><i class="bi bi-bar-chart-line-fill text-primary me-2"></i>Barangay Hot Spots</h6>
                </div>
                <div class="card-body">
                    <div style="position: relative; height: 250px;">
                        <canvas id="barangayFrequencyChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Advanced Analytics Section -->
    <div class="row g-4 mb-4">
        <div class="col-12">
            <h5 class="fw-bold text-dark pb-1 mb-0"><i class="bi bi-graph-up-arrow me-2 text-primary"></i>Advanced Analytics</h5>
        </div>
        
        <!-- Agency Response Time Bar Chart -->
        <div class="col-12 col-lg-5">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-header bg-white py-3 border-0">
                    <h6 class="mb-0 fw-bold text-dark"><i class="bi bi-stopwatch text-primary me-2"></i>Avg. Resolution Time by Agency (Hours)</h6>
                </div>
                <div class="card-body">
                    <div style="position: relative; height: 250px;">
                        <canvas id="responseTimesChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- Seasonal Trends Doughnut -->
        <div class="col-12 col-md-5 col-lg-3">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-header bg-white py-3 border-0">
                    <h6 class="mb-0 fw-bold text-dark"><i class="bi bi-cloud-sun text-primary me-2"></i>Seasonal Analysis</h6>
                </div>
                <div class="card-body">
                    <div style="position: relative; height: 250px;">
                        <canvas id="seasonalChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- Redundancy Hotspots Table -->
        <div class="col-12 col-md-7 col-lg-4">
            <div class="card shadow-sm border-0 h-100 border-start border-danger border-4">
                <div class="card-header bg-white py-3 border-0">
                    <h6 class="mb-0 fw-bold text-dark"><i class="bi bi-exclamation-octagon text-danger me-2"></i>Redundancy Hotspots</h6>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive" style="max-height: 250px; overflow-y: auto;">
                        <table class="table table-sm table-hover mb-0 fs-7">
                            <thead class="table-light sticky-top">
                                <tr>
                                    <th class="ps-3 py-2 text-muted fw-semibold small">Barangay</th>
                                    <th class="py-2 text-muted fw-semibold small">Incident Type</th>
                                    <th class="pe-3 py-2 text-end text-muted fw-semibold small">Cases</th>
                                </tr>
                            </thead>
                            <tbody id="redundancy-table-body">
                                <tr><td colspan="3" class="text-center py-3 text-muted">Gathering data...</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Incidents Grid -->
    <div class="row">
        <div class="col-12">
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header bg-white py-3 border-0 d-flex justify-content-between align-items-center">
                    <h6 class="mb-0 fw-bold text-dark"><i class="bi bi-table text-primary me-2"></i>Recent Incident Activity</h6>
                    <a href="{{ route('admin.incidents.index') }}" class="btn btn-sm btn-link p-0 text-decoration-none">Open Full List</a>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light text-muted small">
                                <tr>
                                    <th class="px-4 py-3">Tracking #</th>
                                    <th class="py-3">Type</th>
                                    <th class="py-3">Priority</th>
                                    <th class="py-3">Status</th>
                                    <th class="py-3">Reported At</th>
                                    <th class="px-4 py-3 text-end">Actions</th>
                                </tr>
                            </thead>
                            <tbody id="admin-dashboard-table-body">
                                <tr><td colspan="6" class="text-center py-4 text-muted">Loading live activity feed...</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Toast Notification Container -->
    <div class="toast-container position-fixed bottom-0 end-0 p-3" style="z-index: 1080;">
        <div id="new-incident-toast" class="toast align-items-center text-white bg-primary border-0" role="alert" aria-live="assertive" aria-atomic="true">
            <div class="d-flex text-white p-2 align-items-center">
                <div class="toast-body flex-grow-1">
                    <strong class="d-block"><i class="bi bi-bell-fill me-2 animate-bounce"></i>New Incident Report!</strong>
                    <span id="toast-message-content">A new anonymous report has been submitted.</span>
                </div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
        </div>
    </div>

    @push('scripts')
        <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
        <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
        <script>
            (function () {
                let mapInstance = null;
                let mapMarkers = [];
                let lastReportCount = null;
                let weeklyTrendsChart = null;
                let categoryDistributionChart = null;
                let barangayFrequencyChart = null;
                let responseTimesChart = null;
                let seasonalChart = null;

                // Audio tone synthesizer using Web Audio API
                function playAlertTone() {
                    try {
                        const AudioContext = window.AudioContext || window.webkitAudioContext;
                        if (!AudioContext) return;
                        const ctx = new AudioContext();
                        const osc = ctx.createOscillator();
                        const gain = ctx.createGain();
                        osc.connect(gain);
                        gain.connect(ctx.destination);
                        osc.type = 'sine';
                        osc.frequency.setValueAtTime(587.33, ctx.currentTime); // D5 tone
                        gain.gain.setValueAtTime(0.15, ctx.currentTime);
                        osc.start();
                        gain.gain.exponentialRampToValueAtTime(0.01, ctx.currentTime + 0.35);
                        osc.stop(ctx.currentTime + 0.35);
                    } catch (e) {
                        console.log('Audio playback permission blocked');
                    }
                }

                // Initialize Map
                function initMap() {
                    mapInstance = L.map('admin-dashboard-map').setView([18.4720, 121.3250], 12);
                    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                        maxZoom: 19,
                        attribution: '&copy; OpenStreetMap contributors'
                    }).addTo(mapInstance);
                }

                // Update Map Pins
                function updateMapMarkers(incidents) {
                    if (!mapInstance) return;
                     
                    // Clear existing markers
                    mapMarkers.forEach(marker => mapInstance.removeLayer(marker));
                    mapMarkers = [];
                    const bounds = [];
 
                    incidents.forEach(inc => {
                        const lat = parseFloat(inc.latitude);
                        const lng = parseFloat(inc.longitude);
                        if (Number.isFinite(lat) && Number.isFinite(lng)) {
                            const marker = L.marker([lat, lng]).addTo(mapInstance);
                            const type = inc.incident_type?.name ?? 'Incident';
                            const status = inc.status?.toUpperCase() ?? 'SUBMITTED';
                            marker.bindPopup(`
                                <strong>Tracking #: ${inc.tracking_number}</strong><br>
                                Type: ${type}<br>
                                Status: <span class="badge bg-secondary">${status}</span><br>
                                <a href="/admin/incidents/${inc.id}" class="btn btn-xs btn-primary text-white py-0 px-2 mt-1 fs-8" style="font-size: 0.75rem;">View File</a>
                            `);
                            mapMarkers.push(marker);
                            bounds.push([lat, lng]);
                        }
                    });
 
                    if (bounds.length === 1) {
                        mapInstance.setView(bounds[0], 12);
                    } else if (bounds.length > 1) {
                        mapInstance.fitBounds(bounds, { padding: [40, 40] });
                    }
                }

                // Fetch data from Dashboard API
                async function fetchMetrics() {
                    try {
                        const res = await fetch('/admin/dashboard.json', {
                            headers: { 'Accept': 'application/json' },
                            credentials: 'same-origin'
                        });
                        const data = await res.json();
                        if (!data) return;

                        // Check for new incident count to trigger Toast & audio
                        if (lastReportCount !== null && data.total_incidents > lastReportCount) {
                            playAlertTone();
                            const toastEl = document.getElementById('new-incident-toast');
                            const toastContent = document.getElementById('toast-message-content');
                            toastContent.textContent = `A new incident has been reported. Total reports: ${data.total_incidents}`;
                            const bootstrapToast = new bootstrap.Toast(toastEl);
                            bootstrapToast.show();
                        }
                        lastReportCount = data.total_incidents;

                        // Update KPIs
                        document.getElementById('kpi-total').textContent = data.total_incidents;
                        document.getElementById('kpi-submitted').textContent = data.incident_status_breakdown?.submitted ?? 0;
                        document.getElementById('kpi-in_progress').textContent = data.incident_status_breakdown?.in_progress ?? 0;
                        document.getElementById('kpi-resolved').textContent =
                            (data.incident_status_breakdown?.resolved ?? 0) + (data.incident_status_breakdown?.closed ?? 0);

                        // Update System Controls
                        document.getElementById('control-agencies').textContent = data.active_agencies;
                        document.getElementById('control-assignments').textContent = data.active_assignments;
                        document.getElementById('control-sms-sent').textContent = data.sms_stats?.sent ?? 0;
                        document.getElementById('control-avg-resolution').textContent = (data.analytics?.avg_resolution_hours ?? 0) + ' hrs';

                        // Update Charts
                        updateCharts(data.analytics);

                        // Render Activity Rows
                        renderActivityTable(data.recent_incidents);

                        // Update Map Pinpoints
                        updateMapMarkers(data.recent_incidents);

                    } catch (e) {
                        console.error('Error loading dashboard analytics', e);
                    }
                }

                function updateCharts(analytics) {
                    if (!analytics) return;

                    // 1. Weekly Trends Chart
                    const weeklyData = analytics.weekly_trends || [];
                    const weeklyLabels = weeklyData.map(item => item.label);
                    const weeklyValues = weeklyData.map(item => item.count);

                    if (weeklyTrendsChart) {
                        weeklyTrendsChart.data.labels = weeklyLabels;
                        weeklyTrendsChart.data.datasets[0].data = weeklyValues;
                        weeklyTrendsChart.update();
                    } else {
                        const ctx = document.getElementById('weeklyTrendsChart').getContext('2d');
                        weeklyTrendsChart = new Chart(ctx, {
                            type: 'line',
                            data: {
                                labels: weeklyLabels,
                                datasets: [{
                                    label: 'Incident Reports',
                                    data: weeklyValues,
                                    borderColor: '#3b82f6',
                                    backgroundColor: 'rgba(59, 130, 246, 0.1)',
                                    fill: true,
                                    tension: 0.3,
                                    borderWidth: 2
                                }]
                            },
                            options: {
                                responsive: true,
                                maintainAspectRatio: false,
                                plugins: {
                                    legend: { display: false }
                                },
                                scales: {
                                    y: {
                                        beginAtZero: true,
                                        ticks: { precision: 0 }
                                    }
                                }
                            }
                        });
                    }

                    // 2. Category Distribution Chart (Doughnut)
                    const categoryData = analytics.categories || {};
                    const categoryLabels = Object.keys(categoryData);
                    const categoryValues = Object.values(categoryData);

                    if (categoryDistributionChart) {
                        categoryDistributionChart.data.labels = categoryLabels;
                        categoryDistributionChart.data.datasets[0].data = categoryValues;
                        categoryDistributionChart.update();
                    } else {
                        const ctx = document.getElementById('categoryDistributionChart').getContext('2d');
                        categoryDistributionChart = new Chart(ctx, {
                            type: 'doughnut',
                            data: {
                                labels: categoryLabels,
                                datasets: [{
                                    data: categoryValues,
                                    backgroundColor: [
                                        '#ef4444', '#f59e0b', '#10b981', '#3b82f6', '#8b5cf6', '#ec4899', '#6b7280'
                                    ]
                                }]
                            },
                            options: {
                                responsive: true,
                                maintainAspectRatio: false,
                                plugins: {
                                    legend: {
                                        position: 'bottom',
                                        labels: { boxWidth: 12, font: { size: 11 } }
                                    }
                                }
                            }
                        });
                    }

                    // 3. Barangay Frequency Chart (Horizontal Bar Chart)
                    const barangayData = analytics.barangays || {};
                    const barangayLabels = Object.keys(barangayData);
                    const barangayValues = Object.values(barangayData);

                    if (barangayFrequencyChart) {
                        barangayFrequencyChart.data.labels = barangayLabels;
                        barangayFrequencyChart.data.datasets[0].data = barangayValues;
                        barangayFrequencyChart.update();
                    } else {
                        const ctx = document.getElementById('barangayFrequencyChart').getContext('2d');
                        barangayFrequencyChart = new Chart(ctx, {
                            type: 'bar',
                            data: {
                                labels: barangayLabels,
                                datasets: [{
                                    label: 'Incidents',
                                    data: barangayValues,
                                    backgroundColor: '#10b981',
                                    borderRadius: 4
                                }]
                            },
                            options: {
                                indexAxis: 'y',
                                responsive: true,
                                maintainAspectRatio: false,
                                plugins: {
                                    legend: { display: false }
                                },
                                scales: {
                                    x: {
                                        beginAtZero: true,
                                        ticks: { precision: 0 }
                                    }
                                }
                            }
                        });
                    }

                    // 4. Agency Response Times Chart
                    const responseTimeData = analytics.agency_response_times || {};
                    const rtLabels = Object.keys(responseTimeData);
                    const rtValues = Object.values(responseTimeData);

                    if (responseTimesChart) {
                        responseTimesChart.data.labels = rtLabels;
                        responseTimesChart.data.datasets[0].data = rtValues;
                        responseTimesChart.update();
                    } else {
                        const ctx = document.getElementById('responseTimesChart').getContext('2d');
                        responseTimesChart = new Chart(ctx, {
                            type: 'bar',
                            data: {
                                labels: rtLabels,
                                datasets: [{
                                    label: 'Avg Hours to Resolve',
                                    data: rtValues,
                                    backgroundColor: '#8b5cf6',
                                    borderRadius: 4
                                }]
                            },
                            options: {
                                responsive: true,
                                maintainAspectRatio: false,
                                plugins: { legend: { display: false } },
                                scales: { y: { beginAtZero: true } }
                            }
                        });
                    }

                    // 5. Seasonal Trends Chart
                    const seasonData = analytics.seasonal_counts || {};
                    const sLabels = Object.keys(seasonData);
                    const sValues = Object.values(seasonData);

                    if (seasonalChart) {
                        seasonalChart.data.labels = sLabels;
                        seasonalChart.data.datasets[0].data = sValues;
                        seasonalChart.update();
                    } else {
                        const ctx = document.getElementById('seasonalChart').getContext('2d');
                        seasonalChart = new Chart(ctx, {
                            type: 'pie',
                            data: {
                                labels: sLabels,
                                datasets: [{
                                    data: sValues,
                                    backgroundColor: ['#f59e0b', '#3b82f6'] // Orange for Dry, Blue for Wet
                                }]
                            },
                            options: {
                                responsive: true,
                                maintainAspectRatio: false,
                                plugins: { legend: { position: 'bottom', labels: { boxWidth: 12, font: { size: 11 } } } }
                            }
                        });
                    }

                    // 6. Redundancy Table
                    const redundancyData = analytics.redundancy_hotspots || [];
                    const rBody = document.getElementById('redundancy-table-body');
                    if (rBody) {
                        rBody.innerHTML = '';
                        if (redundancyData.length === 0) {
                            rBody.innerHTML = '<tr><td colspan="3" class="text-center py-3 text-muted">No recurring incidents found.</td></tr>';
                        } else {
                            redundancyData.forEach(item => {
                                rBody.innerHTML += `
                                    <tr>
                                        <td class="ps-3 py-2 fw-semibold text-dark">${item.barangay}</td>
                                        <td class="py-2"><span class="badge bg-light text-dark border">${item.type}</span></td>
                                        <td class="pe-3 py-2 text-end"><span class="badge bg-danger rounded-pill">${item.count}</span></td>
                                    </tr>
                                `;
                            });
                        }
                    }
                }

                function renderActivityTable(items) {
                    const tbody = document.getElementById('admin-dashboard-table-body');
                    if (!tbody) return;
                    tbody.innerHTML = '';

                    if (!items || items.length === 0) {
                        tbody.innerHTML = '<tr><td colspan="6" class="text-center py-4 text-muted">No recent activities found.</td></tr>';
                        return;
                    }

                    items.forEach(inc => {
                        const type = inc.incident_type?.name ?? '—';
                        const priority = inc.priority ?? 'medium';
                        const status = inc.status ?? 'submitted';
                        const date = inc.reported_at ? new Date(inc.reported_at).toLocaleString() : '—';
                        
                        let priorityClass = 'bg-info text-dark';
                        if (priority === 'medium') priorityClass = 'bg-warning text-dark';
                        else if (priority === 'high') priorityClass = 'bg-danger text-white';
                        else if (priority === 'critical') priorityClass = 'bg-dark text-white';

                        let statusClass = 'bg-secondary';
                        if (status === 'resolved') statusClass = 'bg-success';
                        else if (status === 'in_progress') statusClass = 'bg-primary';

                        const row = document.createElement('tr');
                        row.innerHTML = `
                            <td class="px-4 py-3 fw-bold text-primary">${inc.tracking_number}</td>
                            <td class="py-3"><span class="badge bg-light text-dark border">${type}</span></td>
                            <td class="py-3"><span class="badge ${priorityClass} text-capitalize">${priority}</span></td>
                            <td class="py-3"><span class="badge ${statusClass} text-capitalize">${status.replace('_', ' ')}</span></td>
                            <td class="py-3 text-muted small">${date}</td>
                            <td class="px-4 py-3 text-end">
                                <a href="/admin/incidents/${inc.id}" class="btn btn-sm btn-outline-primary py-1">
                                    <i class="bi bi-eye"></i> View
                                </a>
                            </td>
                        `;
                        tbody.appendChild(row);
                    });
                }

                // Bootstrapping
                document.addEventListener('DOMContentLoaded', function() {
                    // Only initialize map on first load, not on every poll
                    let mapInitialized = false;
                    
                    function initDashboard() {
                        if (!mapInitialized) {
                            initMap();
                            mapInitialized = true;
                        }
                        fetchMetrics();
                    }

                    // Load dashboard immediately
                    initDashboard();
                    
                    // Setup 60-second polling for real-time dashboard updates (reduced from 10s for better performance)
                    // Only poll when tab is visible to save resources
                    let pollInterval = setInterval(function() {
                        if (document.visibilityState === 'visible') {
                            fetchMetrics();
                        }
                    }, 60000);

                    // Pause polling when tab is hidden
                    document.addEventListener('visibilitychange', function() {
                        if (document.visibilityState === 'hidden') {
                            // Tab hidden - polling continues but metric fetch only happens on visible
                        } else if (document.visibilityState === 'visible') {
                            // Tab visible - fetch immediately to show latest data
                            fetchMetrics();
                        }
                    });
                });
            })();
        </script>
    @endpush
</x-app-layout>

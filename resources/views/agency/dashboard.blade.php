<x-app-layout>
    <x-slot name="header">
        {{ __('Government Agency Response Center') }}
    </x-slot>

    @push('styles')
        <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="">
        <style>
            #agency-dashboard-map {
                height: 320px;
                border-radius: 0.75rem;
                border: 1px solid #dee2e6;
                z-index: 1;
            }
        </style>
    @endpush

    <!-- Status KPI Grid -->
    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="card h-100 shadow-sm border-0 border-start border-primary border-4">
                <div class="card-body">
                    <div class="text-muted small text-uppercase fw-semibold">Assigned Incident Cases</div>
                    <h2 class="fw-bold mb-0 text-dark mt-1" id="kpi-assigned">—</h2>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card h-100 shadow-sm border-0 border-start border-warning border-4">
                <div class="card-body">
                    <div class="text-muted small text-uppercase fw-semibold">Pending Resolutions</div>
                    <h2 class="fw-bold mb-0 text-dark mt-1" id="kpi-pending">—</h2>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card h-100 shadow-sm border-0 border-start border-success border-4">
                <div class="card-body">
                    <div class="text-muted small text-uppercase fw-semibold">SMS Alerts Received (This Week)</div>
                    <h2 class="fw-bold mb-0 text-dark mt-1" id="kpi-sms">—</h2>
                </div>
            </div>
        </div>
    </div>

    <!-- Active Details Grid -->
    <div class="row g-4 mb-4">
        <div class="col-lg-7">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-header bg-white py-3 border-0">
                    <h6 class="mb-0 fw-bold text-dark"><i class="bi bi-geo-alt-fill text-primary me-2"></i>Active Emergency Dispatches Map</h6>
                </div>
                <div class="card-body p-2">
                    <div id="agency-dashboard-map"></div>
                </div>
            </div>
        </div>

        <div class="col-lg-5">
            <div class="card h-100 shadow-sm border-0">
                <div class="card-header bg-white py-3 border-0 d-flex justify-content-between align-items-center">
                    <h6 class="mb-0 fw-bold text-dark"><i class="bi bi-clock-history text-primary me-2"></i>Recent Status Changes Feed</h6>
                </div>
                <div class="card-body p-0">
                    <div class="list-group list-group-flush" id="agency-status-feed" style="max-height: 320px; overflow-y: auto;">
                        <div class="text-center py-5 text-muted small">Loading live activity feed...</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Active Emergency Dispatches List -->
    <div class="row">
        <div class="col-12">
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header bg-white py-3 border-0 d-flex justify-content-between align-items-center">
                    <h6 class="mb-0 fw-bold text-dark"><i class="bi bi-card-checklist text-primary me-2"></i>Active Emergency Dispatches</h6>
                    <a href="{{ route('agency.incidents.index') }}" class="btn btn-sm btn-link p-0 text-decoration-none">Open Full List</a>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light text-muted small">
                                <tr>
                                    <th class="px-4 py-3">Tracking #</th>
                                    <th class="py-3">Type</th>
                                    <th class="py-3">Priority</th>
                                    <th class="py-3">Barangay</th>
                                    <th class="py-3">Status</th>
                                    <th class="py-3">Reported At</th>
                                    <th class="px-4 py-3 text-end">Actions</th>
                                </tr>
                            </thead>
                            <tbody id="agency-dispatches-table-body">
                                <tr><td colspan="7" class="text-center py-4 text-muted">Loading active dispatches...</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Toast Notification Container -->
    <div class="toast-container position-fixed bottom-0 end-0 p-3" style="z-index: 1080;">
        <div id="agency-new-toast" class="toast align-items-center text-white bg-danger border-0" role="alert" aria-live="assertive" aria-atomic="true">
            <div class="d-flex text-white p-2 align-items-center">
                <div class="toast-body flex-grow-1">
                    <strong class="d-block"><i class="bi bi-exclamation-triangle-fill me-2 animate-bounce"></i>New Emergency Dispatched!</strong>
                    <span id="agency-toast-message">You have been assigned a new incident. Check dispatches immediately.</span>
                </div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
        </div>
    </div>

    @push('scripts')
        <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
        <script>
            (function () {
                let mapInstance = null;
                let mapMarkers = [];
                let lastAssignmentCount = null;

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
                        osc.type = 'sawtooth'; // Louder sawtooth waveform for emergencies
                        osc.frequency.setValueAtTime(880, ctx.currentTime); // A5 note
                        gain.gain.setValueAtTime(0.2, ctx.currentTime);
                        osc.start();
                        gain.gain.exponentialRampToValueAtTime(0.01, ctx.currentTime + 0.5);
                        osc.stop(ctx.currentTime + 0.5);
                    } catch (e) {
                        console.log('Audio playback permission blocked');
                    }
                }

                // Initialize Map
                function initMap() {
                    mapInstance = L.map('agency-dashboard-map').setView([18.4720, 121.3250], 12);
                    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                        maxZoom: 19,
                        attribution: '&copy; OpenStreetMap contributors'
                    }).addTo(mapInstance);
                }

                // Fetch metrics from Agency Dashboard API
                async function fetchMetrics() {
                    try {
                        const res = await fetch('/agency/dashboard.json', {
                            headers: { 'Accept': 'application/json' },
                            credentials: 'same-origin'
                        });
                        const data = await res.json();
                        if (!data) return;

                        // Check for new assignments to trigger alert
                        if (lastAssignmentCount !== null && data.total_assigned_incidents > lastAssignmentCount) {
                            playAlertTone();
                            const toastEl = document.getElementById('agency-new-toast');
                            const toastMsg = document.getElementById('agency-toast-message');
                            toastMsg.textContent = `A new emergency report has been assigned. You now have ${data.total_assigned_incidents} assigned dispatches.`;
                            const bootstrapToast = new bootstrap.Toast(toastEl);
                            bootstrapToast.show();
                        }
                        lastAssignmentCount = data.total_assigned_incidents;

                        // Update KPIs
                        document.getElementById('kpi-assigned').textContent = data.total_assigned_incidents;
                        document.getElementById('kpi-pending').textContent = data.pending_resolutions;
                        document.getElementById('kpi-sms').textContent = data.sms_alerts_this_week;

                        // Update Active Dispatches Table
                        renderDispatchesTable(data.active_dispatches);

                        // Update Feed
                        renderFeed(data.recent_status_updates);

                        // Update Map using active dispatches (includes coordinates)
                        updateMapMarkers(data.active_dispatches);

                    } catch (e) {
                        console.error('Error loading agency dashboard analytics', e);
                    }
                }

                function renderDispatchesTable(items) {
                    const tbody = document.getElementById('agency-dispatches-table-body');
                    if (!tbody) return;
                    tbody.innerHTML = '';

                    if (!items || items.length === 0) {
                        tbody.innerHTML = '<tr><td colspan="7" class="text-center py-4 text-muted">No active emergency dispatches assigned at this time.</td></tr>';
                        return;
                    }

                    items.forEach(inc => {
                        const type = inc.incident_type?.name ?? '—';
                        const priority = inc.priority ?? 'medium';
                        const status = inc.status ?? 'assigned';
                        const barangay = inc.barangay ?? '—';
                        const date = inc.reported_at ? new Date(inc.reported_at).toLocaleString() : '—';
                        
                        let priorityClass = 'bg-info text-dark';
                        if (priority === 'medium') priorityClass = 'bg-warning text-dark';
                        else if (priority === 'high') priorityClass = 'bg-danger text-white';
                        else if (priority === 'critical') priorityClass = 'bg-dark text-white';

                        let statusClass = 'bg-secondary';
                        if (status === 'assigned') statusClass = 'bg-info text-dark';
                        else if (status === 'in_progress') statusClass = 'bg-primary';
                        else if (status === 'pending_info') statusClass = 'bg-warning text-dark';

                        const row = document.createElement('tr');
                        row.innerHTML = `
                            <td class="px-4 py-3 fw-bold text-primary">${inc.tracking_number}</td>
                            <td class="py-3"><span class="badge bg-light text-dark border">${type}</span></td>
                            <td class="py-3"><span class="badge ${priorityClass} text-capitalize">${priority}</span></td>
                            <td class="py-3 text-muted">${barangay}</td>
                            <td class="py-3"><span class="badge ${statusClass} text-capitalize">${status.replace('_', ' ')}</span></td>
                            <td class="py-3 text-muted small">${date}</td>
                            <td class="px-4 py-3 text-end">
                                <a href="/agency/incidents/${inc.id}" class="btn btn-sm btn-outline-primary py-1">
                                    <i class="bi bi-eye"></i> Process
                                </a>
                            </td>
                        `;
                        tbody.appendChild(row);
                    });
                }

                function renderFeed(updates) {
                    const feed = document.getElementById('agency-status-feed');
                    if (!feed) return;
                    feed.innerHTML = '';

                    if (!updates || updates.length === 0) {
                        feed.innerHTML = '<div class="text-center py-5 text-muted small">No recent status updates logged.</div>';
                        return;
                    }

                    updates.forEach(up => {
                        const div = document.createElement('div');
                        div.className = 'list-group-item p-3 border-0 border-bottom';
                        const time = new Date(up.created_at).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
                        const date = new Date(up.created_at).toLocaleDateString([], { month: 'short', day: 'numeric' });
                        div.innerHTML = `
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <span class="badge bg-primary px-2 py-1">${up.to_status.replace('_', ' ').toUpperCase()}</span>
                                <small class="text-muted">${date} at ${time}</small>
                            </div>
                            <p class="mb-0 text-muted small" style="white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                                ${up.comment ?? 'No comment provided.'}
                            </p>
                            <a href="/agency/incidents/${up.incident_id}" class="fs-8 text-decoration-none mt-1 d-inline-block">Open Case File</a>
                        `;
                        feed.appendChild(div);
                    });
                }

                function updateMapMarkers(incidents) {
                    if (!mapInstance) return;

                    // Clear existing markers
                    mapMarkers.forEach(marker => mapInstance.removeLayer(marker));
                    mapMarkers = [];

                    const bounds = [];

                    if (!Array.isArray(incidents) || incidents.length === 0) {
                        return;
                    }

                    incidents.forEach(inc => {
                        const lat = parseFloat(inc.latitude);
                        const lng = parseFloat(inc.longitude);
                        if (Number.isFinite(lat) && Number.isFinite(lng)) {
                            const marker = L.marker([lat, lng]).addTo(mapInstance);
                            const type = inc.incident_type?.name ?? 'Incident';
                            marker.bindPopup(`
                                <strong>Tracking #: ${inc.tracking_number}</strong><br>
                                Type: ${type}<br>
                                Status: <span class="badge bg-secondary">${(inc.status||'').toUpperCase()}</span><br>
                                <a href="/agency/incidents/${inc.id}" class="btn btn-xs btn-primary text-white py-0 px-2 mt-1 fs-8" style="font-size: 0.75rem;">Process Case</a>
                            `);
                            mapMarkers.push(marker);
                            bounds.push([lat, lng]);
                        }
                    });

                    if (bounds.length === 1) {
                        mapInstance.setView(bounds[0], 14);
                    } else if (bounds.length > 1) {
                        mapInstance.fitBounds(bounds, { padding: [40, 40] });
                    }
                }

                // Bootstrapping
                document.addEventListener('DOMContentLoaded', function() {
                    initMap();
                    fetchMetrics();
                    // Setup 10-second polling for real-time dispatches updates
                    setInterval(fetchMetrics, 10000);
                });
            })();
        </script>
    @endpush
</x-app-layout>

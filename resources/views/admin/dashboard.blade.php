<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between gap-4 flex-wrap">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Administrator Dashboard') }}
            </h2>
            <div class="text-sm text-gray-600">
                {{ __('View incident activity and manage public status updates.') }}
            </div>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-4">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg border border-gray-100">
                    <div class="p-5">
                        <div class="text-sm text-gray-500">{{ __('Total Incidents') }}</div>
                        <div class="mt-2 text-2xl font-semibold text-gray-900" id="kpi-incidents">—</div>
                    </div>
                </div>

                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg border border-gray-100">
                    <div class="p-5">
                        <div class="text-sm text-gray-500">{{ __('Submitted (Public)') }}</div>
                        <div class="mt-2 text-2xl font-semibold text-gray-900" id="kpi-submitted">—</div>
                    </div>
                </div>

                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg border border-gray-100">
                    <div class="p-5">
                        <div class="text-sm text-gray-500">{{ __('In Progress') }}</div>
                        <div class="mt-2 text-2xl font-semibold text-gray-900" id="kpi-in_progress">—</div>
                    </div>
                </div>

                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg border border-gray-100">
                    <div class="p-5">
                        <div class="text-sm text-gray-500">{{ __('Resolved') }}</div>
                        <div class="mt-2 text-2xl font-semibold text-gray-900" id="kpi-resolved">—</div>
                    </div>
                </div>
            </div>

            <div class="mt-6 bg-white overflow-hidden shadow-sm sm:rounded-lg border border-gray-100">
                <div class="p-5 border-b border-gray-100 flex items-center justify-between flex-wrap gap-3">
                    <div>
                        <div class="text-gray-900 font-semibold">{{ __('Recent Incidents') }}</div>
                        <div class="text-gray-600 text-sm">{{ __('Latest reported incidents for quick review.') }}</div>
                    </div>
                    <a class="text-blue-600 hover:text-blue-800 text-sm font-medium" href="{{ route('admin.incidents.index') }}">
                        {{ __('Open incidents list') }}
                    </a>
                </div>

                <div class="p-5">
                    <div class="overflow-x-auto">
                        <table class="min-w-full text-sm">
                            <thead class="text-left text-gray-500">
                                <tr>
                                    <th class="pb-3 pr-4 font-medium">{{ __('Tracking #') }}</th>
                                    <th class="pb-3 pr-4 font-medium">{{ __('Type') }}</th>
                                    <th class="pb-3 pr-4 font-medium">{{ __('Priority') }}</th>
                                    <th class="pb-3 pr-4 font-medium">{{ __('Status') }}</th>
                                    <th class="pb-3 pr-4 font-medium">{{ __('Reported At') }}</th>
                                </tr>
                            </thead>
                            <tbody id="incidents-table-body" class="text-gray-800">
                                <tr><td colspan="5" class="py-4 text-gray-500">{{ __('Loading...') }}</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
        <script>
            // Minimal, non-blocking dashboard bootstrap.
            // Backend endpoints can be added later; this view stays compatible.
            (function () {
                const fetchJson = async (url) => {
                    try {
                        const res = await fetch(url, { headers: { 'Accept': 'application/json' } });
                        return await res.json();
                    } catch (e) {
                        return null;
                    }
                };

                const kpi = {
                    incidents: document.getElementById('kpi-incidents'),
                    submitted: document.getElementById('kpi-submitted'),
                    in_progress: document.getElementById('kpi-in_progress'),
                    resolved: document.getElementById('kpi-resolved'),
                };

                const renderRows = (items) => {
                    const tbody = document.getElementById('incidents-table-body');
                    if (!tbody) return;
                    tbody.innerHTML = '';

                    if (!items || !Array.isArray(items) || items.length === 0) {
                        tbody.innerHTML = '<tr><td colspan="5" class="py-4 text-gray-500">No incidents found.</td></tr>';
                        return;
                    }

                    items.forEach((inc) => {
                        const type = inc.incident_type?.name ?? inc.incidentType?.name ?? '—';
                        const priority = inc.priority?.label ?? inc.priority ?? '—';
                        const status = inc.status?.value ?? inc.status ?? '—';
                        const reported = inc.reported_at ?? '—';
                        const row = document.createElement('tr');
                        row.innerHTML = `
                            <td class="py-3 pr-4 whitespace-nowrap">${inc.tracking_number ?? '—'}</td>
                            <td class="py-3 pr-4 whitespace-nowrap">${type}</td>
                            <td class="py-3 pr-4 whitespace-nowrap">${priority}</td>
                            <td class="py-3 pr-4 whitespace-nowrap">${status}</td>
                            <td class="py-3 pr-4 whitespace-nowrap">${reported}</td>
                        `;
                        tbody.appendChild(row);
                    });
                };

                const populate = async () => {
                    const data = await fetchJson('/admin/dashboard.json');
                    if (!data) return;

                    if (data.total_incidents !== undefined) kpi.incidents.textContent = data.total_incidents;
                    if (data.incident_status_breakdown) {
                        const statuses = data.incident_status_breakdown;
                        if (kpi.submitted) kpi.submitted.textContent = statuses['submitted'] ?? 0;
                        if (kpi.in_progress) kpi.in_progress.textContent = statuses['in_progress'] ?? 0;
                        if (kpi.resolved) kpi.resolved.textContent = statuses['resolved'] ?? 0;
                    }

                    if (data.recent_incidents) renderRows(data.recent_incidents);
                };

                populate();
            })();
        </script>
    @endpush
</x-app-layout>


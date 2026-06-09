<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between gap-4 flex-wrap">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Agency Dashboard') }}
            </h2>
            <div class="text-sm text-gray-600">
                {{ __('Review assigned incidents and maintain status updates.') }}
            </div>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg border border-gray-100">
                <div class="p-5 border-b border-gray-100">
                    <div class="text-gray-900 font-semibold">{{ __('Welcome') }}</div>
                    <div class="text-gray-600 text-sm">
                        {{ __('This dashboard is currently a UI scaffold; backend API endpoints can be added without changing routing consistency.') }}
                    </div>
                </div>

                <div class="p-5">
                    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
                        <div class="p-4 bg-gray-50 rounded-lg border border-gray-100">
                            <div class="text-sm text-gray-500">{{ __('Assigned Incidents') }}</div>
                            <div class="mt-1 text-lg font-semibold text-gray-900" id="assigned-incidents">—</div>
                        </div>
                        <div class="p-4 bg-gray-50 rounded-lg border border-gray-100">
                            <div class="text-sm text-gray-500">{{ __('Pending Resolutions') }}</div>
                            <div class="mt-1 text-lg font-semibold text-gray-900" id="pending-resolutions">—</div>
                        </div>
                        <div class="p-4 bg-gray-50 rounded-lg border border-gray-100">
                            <div class="text-sm text-gray-500">{{ __('SMS Alerts (Week)') }}</div>
                            <div class="mt-1 text-lg font-semibold text-gray-900" id="sms-alerts">—</div>
                        </div>
                    </div>
                </div>

                <div class="mt-6 bg-white overflow-hidden shadow-sm sm:rounded-lg border border-gray-100">
                    <div class="p-5 border-b border-gray-100">
                        <div class="text-gray-900 font-semibold">{{ __('Status Breakdown') }}</div>
                        <div class="text-gray-600 text-sm">{{ __('Current status distribution of assigned incidents.') }}</div>
                    </div>
                    <div class="p-5">
                        <div id="status-breakdown" class="space-y-2">Loading...</div>
                    </div>
                </div>

                <script>
                    (async () => {
                        try {
                            const res = await fetch('/agency/dashboard.json');
                            const data = await res.json();

                            document.getElementById('assigned-incidents').textContent = data.total_assigned_incidents ?? 0;
                            document.getElementById('pending-resolutions').textContent = data.pending_resolutions ?? 0;
                            document.getElementById('sms-alerts').textContent = data.sms_alerts_this_week ?? 0;

                            const statuses = data.incident_status_breakdown || {};
                            const statusHtml = Object.entries(statuses)
                                .map(([status, count]) => `<div class="flex justify-between p-2 bg-gray-50 rounded"><span class="capitalize">${status}</span><strong>${count}</strong></div>`)
                                .join('') || '<div class="text-gray-500 text-sm">No incidents assigned yet.</div>';

                            document.getElementById('status-breakdown').innerHTML = statusHtml;
                        } catch (e) {
                            console.error('Dashboard load error:', e);
                        }
                    })();
                </script>
            </div>
        </div>
    </div>
</x-app-layout>


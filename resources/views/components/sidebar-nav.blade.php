<!-- Centralized Navigation - Single source of truth for all roles -->
<div class="flex-grow-1 py-3 overflow-y-auto">
    <ul class="nav flex-column">
        <!-- Dashboard (All Roles) -->
        <li class="nav-item">
            <a class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}" href="{{ route('dashboard') }}" data-role="all">
                <i class="bi bi-speedometer2"></i><span>Dashboard</span>
            </a>
        </li>

        <!-- Administrator Menu -->
        @if(auth()->user()->isAdministrator())
            <li class="nav-section-label" data-role="administrator">Incident Management</li>
            <li class="nav-item" data-role="administrator">
                <a class="nav-link {{ request()->routeIs('admin.incidents.*') ? 'active' : '' }}" href="{{ route('admin.incidents.index') }}">
                    <i class="bi bi-exclamation-circle"></i>
                    <span class="flex-grow-1">Incidents</span>
                </a>
            </li>
            <li class="nav-item" data-role="administrator">
                <a class="nav-link {{ request()->routeIs('admin.incident_documents.*') ? 'active' : '' }}" href="{{ route('admin.incident_documents.index') }}">
                    <i class="bi bi-folder2-open"></i><span>Case Documents</span>
                </a>
            </li>
            <li class="nav-item" data-role="administrator">
                <a class="nav-link {{ request()->routeIs('admin.incident_types.*') ? 'active' : '' }}" href="{{ route('admin.incident_types.index') }}">
                    <i class="bi bi-tags-fill"></i><span>Incident Types</span>
                </a>
            </li>
            <li class="nav-item" data-role="administrator">
                <a class="nav-link {{ request()->routeIs('admin.reports.*') ? 'active' : '' }}" href="{{ route('admin.reports.index') }}">
                    <i class="bi bi-file-earmark-text"></i><span>Make Report</span>
                </a>
            </li>

            <li class="nav-section-label" data-role="administrator">Coordination</li>
            <li class="nav-item" data-role="administrator">
                <a class="nav-link {{ request()->routeIs('admin.agencies.*') ? 'active' : '' }}" href="{{ route('admin.agencies.index') }}">
                    <i class="bi bi-building"></i><span>Agencies &amp; Personnel</span>
                </a>
            </li>
            <li class="nav-item" data-role="administrator">
                <a class="nav-link {{ request()->routeIs('admin.document_requests.*') ? 'active' : '' }}" href="{{ route('admin.document_requests.index') }}">
                    <i class="bi bi-file-earmark-pdf"></i><span>Document Requests</span>
                </a>
            </li>
            <li class="nav-item" data-role="administrator">
                <a class="nav-link {{ request()->routeIs('admin.sms-logs') ? 'active' : '' }}" href="{{ route('admin.sms-logs') }}">
                    <i class="bi bi-chat-left-text"></i><span>SMS Alerts</span>
                </a>
            </li>

            <li class="nav-section-label" data-role="administrator">Oversight</li>
            <li class="nav-item" data-role="administrator">
                <a class="nav-link {{ request()->routeIs('admin.audit-logs') ? 'active' : '' }}" href="{{ route('admin.audit-logs') }}">
                    <i class="bi bi-shield-shaded"></i><span>Audit Trails</span>
                </a>
            </li>
            <li class="nav-item" data-role="administrator">
                <a class="nav-link {{ request()->routeIs('admin.feedback.*') ? 'active' : '' }}" href="{{ route('admin.feedback.index') }}">
                    <i class="bi bi-chat-square-text"></i><span>Feedback &amp; Concerns</span>
                </a>
            </li>
        @endif

        <!-- Agency & Personnel Menu -->
        @if(auth()->user()->isAgency() || auth()->user()->isPersonnel())
            <li class="nav-section-label" data-role="agency,personnel">Operations</li>
            <li class="nav-item" data-role="agency,personnel">
                <a class="nav-link {{ request()->routeIs('agency.incidents.*') || request()->routeIs('personnel.incidents.*') ? 'active' : '' }}" href="{{ auth()->user()->isPersonnel() ? route('personnel.incidents.index') : route('agency.incidents.index') }}">
                    <i class="bi bi-card-checklist"></i>
                    <span class="flex-grow-1">Dispatches</span>
                </a>
            </li>

            @if(auth()->user()->agency_id || auth()->user()->isAgency())
                <li class="nav-section-label" data-role="agency,personnel">Records</li>
            @endif
            @if(auth()->user()->agency_id)
                <li class="nav-item" data-role="agency,personnel">
                    <a class="nav-link {{ request()->routeIs('agency.document_requests.*') ? 'active' : '' }}" href="{{ route('agency.document_requests.index') }}">
                        <i class="bi bi-file-earmark-pdf"></i><span>Document Requests</span>
                    </a>
                </li>
            @endif
            @if(auth()->user()->isAgency())
                <li class="nav-item" data-role="agency">
                    <a class="nav-link {{ request()->routeIs('agency.archived_reports.*') ? 'active' : '' }}" href="{{ route('agency.archived_reports.index') }}">
                        <i class="bi bi-clock-history"></i><span>Resolved Reports</span>
                    </a>
                </li>
            @endif
        @endif

        <!-- Account Section (All Roles) -->
        <li class="nav-section-label" data-role="all">Account</li>
        <li class="nav-item" data-role="all">
            <a class="nav-link {{ request()->routeIs('profile.edit') ? 'active' : '' }}" href="{{ route('profile.edit') }}">
                <i class="bi bi-gear"></i><span>My Profile</span>
            </a>
        </li>
        <li class="nav-item" data-role="all">
            <form method="POST" action="{{ route('logout') }}" id="sidebar-logout-form">
                @csrf
                <a class="nav-link text-danger" href="#" onclick="event.preventDefault(); if(this.dataset.submitted) return; this.dataset.submitted='1'; document.getElementById('sidebar-logout-form').submit();">
                    <i class="bi bi-box-arrow-right"></i><span>Log Out</span>
                </a>
            </form>
        </li>
    </ul>
</div>

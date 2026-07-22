<?php

namespace App\Http\Controllers;

use App\Models\SystemNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class NotificationController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();

        $query = SystemNotification::query();

        if ($user->isAdministrator()) {
            $query->where(function ($q) use ($user) {
                $q->where('user_id', $user->id)->orWhereNull('user_id');
            });
        } elseif ($user->isPersonnel()) {
            $query->where('user_id', $user->id);
        } else {
            // Agency users: notifications targeted to their agency (data->agency_id) or global (user_id null),
            // but never the admin-only broadcast row (data->audience === 'admin').
            $agencyId = $user->agency_id;
            $query->where(function ($q) use ($user, $agencyId) {
                $q->where('user_id', $user->id)
                    ->orWhere(function ($q2) use ($agencyId) {
                        $q2->whereNull('user_id')
                            ->where('data->agency_id', $agencyId)
                            ->where(function ($q3) {
                                $q3->whereNull('data->audience')
                                    ->orWhere('data->audience', '!=', 'admin');
                            });
                    });
            });
        }

        $notifications = $query->latest('created_at')->get();

        return view('notifications.index', compact('notifications'));
    }

    public function markAllRead(Request $request): RedirectResponse
    {
        $user = $request->user();

        $query = SystemNotification::query();

        if ($user->isAdministrator()) {
            $query->where(function ($q) use ($user) {
                $q->where('user_id', $user->id)->orWhereNull('user_id');
            });
        } elseif ($user->isPersonnel()) {
            $query->where('user_id', $user->id);
        } else {
            $agencyId = $user->agency_id;
            $query->where(function ($q) use ($user, $agencyId) {
                $q->where('user_id', $user->id)
                    ->orWhere(function ($q2) use ($agencyId) {
                        $q2->whereNull('user_id')
                            ->where('data->agency_id', $agencyId)
                            ->where(function ($q3) {
                                $q3->whereNull('data->audience')
                                    ->orWhere('data->audience', '!=', 'admin');
                            });
                    });
            });
        }

        $query->whereNull('read_at')->update(['read_at' => now()]);

        return redirect()->route('notifications.index')->with('success', 'All notifications marked as read.');
    }

    public function show(SystemNotification $notification, Request $request): RedirectResponse
    {
        $user = $request->user();
        $agencyId = $user->agency_id;

        $allowed = false;

        if ($user->isAdministrator()) {
            $allowed = $notification->user_id === $user->id || is_null($notification->user_id);
        } elseif ($user->isPersonnel()) {
            $allowed = $notification->user_id === $user->id;
        } else {
            $allowed = $notification->user_id === $user->id
                || (
                    is_null($notification->user_id)
                    && data_get($notification->data, 'agency_id') === $agencyId
                    && data_get($notification->data, 'audience') !== 'admin'
                );
        }

        abort_if(! $allowed, 403);

        $notification->markAsRead();

        if ($notification->data['incident_id'] ?? false) {
            if ($user->isAdministrator()) {
                return redirect()->route('admin.incidents.show', $notification->data['incident_id']);
            }

            if ($user->isPersonnel()) {
                return redirect()->route('personnel.incidents.show', $notification->data['incident_id']);
            }

            return redirect()->route('agency.incidents.show', $notification->data['incident_id']);
        }

        return redirect()->route('notifications.index');
    }
}

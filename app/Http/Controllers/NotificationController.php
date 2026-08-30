<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Single controller shared by every role — Notification rows are scoped by
 * user_id, so there's nothing role-specific about reading or acknowledging
 * your own notifications. Keeps admin/agency/personnel from each needing a
 * near-identical copy of this.
 */
class NotificationController extends Controller
{
    /**
     * Lightweight polling endpoint used by the bell dropdown. Returns the
     * unread count plus the most recent notifications (read or not) so the
     * dropdown can render without a full page load.
     */
    public function poll(Request $request): JsonResponse
    {
        $user = $request->user();

        $recent = Notification::where('user_id', $user->id)
            ->orderByDesc('created_at')
            ->limit(8)
            ->get()
            ->map(fn (Notification $n) => $this->transform($n));

        return response()->json([
            'unread_count' => Notification::where('user_id', $user->id)->unread()->count(),
            'notifications' => $recent,
        ]);
    }

    public function index(Request $request): View
    {
        $query = Notification::where('user_id', $request->user()->id)
            ->orderByDesc('created_at');

        if ($request->filled('status') && $request->string('status')->value() === 'unread') {
            $query->unread();
        }

        if ($request->filled('type')) {
            $query->where('type', $request->string('type')->value());
        }

        $notifications = $query->paginate(20)->withQueryString();

        $types = Notification::where('user_id', $request->user()->id)
            ->distinct()
            ->pluck('type');

        return view('notifications.index', [
            'notifications' => $notifications,
            'types' => $types,
        ]);
    }

    public function markRead(Request $request, Notification $notification): JsonResponse|\Illuminate\Http\RedirectResponse
    {
        abort_unless($notification->user_id === $request->user()->id, 403);

        if (! $notification->isRead()) {
            $notification->update(['read_at' => now()]);
        }

        if ($request->wantsJson()) {
            return response()->json(['message' => 'Marked as read.']);
        }

        return redirect($notification->url() ?? route('notifications.index'));
    }

    public function markAllRead(Request $request): JsonResponse|\Illuminate\Http\RedirectResponse
    {
        Notification::where('user_id', $request->user()->id)
            ->unread()
            ->update(['read_at' => now()]);

        if ($request->wantsJson()) {
            return response()->json(['message' => 'All notifications marked as read.']);
        }

        return back()->with('success', 'All notifications marked as read.');
    }

    /**
     * Delete ("move to bin") a single notification.
     */
    public function destroy(Request $request, Notification $notification): JsonResponse|\Illuminate\Http\RedirectResponse
    {
        abort_unless($notification->user_id === $request->user()->id, 403);

        $notification->delete();

        if ($request->wantsJson()) {
            return response()->json(['message' => 'Notification deleted.']);
        }

        return back()->with('success', 'Notification deleted.');
    }

    /**
     * Delete a specific set of notifications the user checked/selected.
     */
    public function destroySelected(Request $request): JsonResponse|\Illuminate\Http\RedirectResponse
    {
        $ids = collect($request->input('ids', []))->filter()->values();

        $deleted = Notification::where('user_id', $request->user()->id)
            ->whereIn('id', $ids)
            ->delete();

        $message = $deleted === 1 ? '1 notification deleted.' : "{$deleted} notifications deleted.";

        if ($request->wantsJson()) {
            return response()->json(['message' => $message, 'deleted' => $deleted]);
        }

        return back()->with('success', $message);
    }

    /**
     * Empty the bin — delete every notification belonging to this user,
     * optionally scoped to the same filters shown on the notifications
     * page (status/type) so "Delete All" only clears what's visible.
     */
    public function destroyAll(Request $request): JsonResponse|\Illuminate\Http\RedirectResponse
    {
        $query = Notification::where('user_id', $request->user()->id);

        if ($request->filled('status') && $request->string('status')->value() === 'unread') {
            $query->unread();
        }

        if ($request->filled('type')) {
            $query->where('type', $request->string('type')->value());
        }

        $deleted = $query->delete();
        $message = $deleted === 0 ? 'No notifications to delete.' : "All notifications deleted ({$deleted}).";

        if ($request->wantsJson()) {
            return response()->json(['message' => $message, 'deleted' => $deleted]);
        }

        return back()->with('success', $message);
    }

    private function transform(Notification $n): array
    {
        return [
            'id' => $n->id,
            'type' => $n->type,
            'title' => $n->title,
            'message' => $n->message,
            'icon' => $n->icon(),
            'color' => $n->color(),
            'target_url' => $n->url(),
            'read_url' => route('notifications.mark_read', $n),
            'delete_url' => route('notifications.destroy', $n),
            'is_read' => $n->isRead(),
            'created_at' => $n->created_at->diffForHumans(),
        ];
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\UserNotification;
use App\Services\Notifications\NotificationTypeRegistry;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class NotificationCenterController extends Controller
{
    public function index(Request $request, string $company_slug, NotificationTypeRegistry $registry)
    {
        $filter = (string) $request->get('filter', '');

        $query = UserNotification::query()
            ->where('user_id', Auth::id())
            ->latest();

        if ($filter === 'dismissed') {
            $query->whereNotNull('dismissed_at');
        } else {
            $query->undismissed();
            if ($filter === 'unread') {
                $query->unread();
            } elseif ($filter === 'read') {
                $query->whereNotNull('read_at');
            }
        }

        if ($type = $request->get('type')) {
            $query->where('type', $type);
        }

        if ($severity = $request->get('severity')) {
            $query->where('severity', $severity);
        }

        $notifications = $query->paginate(25)->withQueryString();

        $typeLabels = [];
        foreach ($registry->all() as $typeKey => $def) {
            $typeLabels[$typeKey] = (string) ($def['label'] ?? $typeKey);
        }

        return view('notifications.index', compact('notifications', 'typeLabels'));
    }

    public function unreadCount(string $company_slug)
    {
        $count = UserNotification::query()
            ->where('user_id', Auth::id())
            ->unread()
            ->count();

        return response()->json(['count' => $count]);
    }

    public function dropdown(string $company_slug)
    {
        $notifications = UserNotification::query()
            ->where('user_id', Auth::id())
            ->undismissed()
            ->latest()
            ->limit(10)
            ->get();

        $unread = UserNotification::query()
            ->where('user_id', Auth::id())
            ->unread()
            ->count();

        return response()->json([
            'unread' => $unread,
            'items' => $notifications->map(fn (UserNotification $n) => $this->serialize($n))->values(),
        ]);
    }

    public function markRead(Request $request, string $company_slug, UserNotification $notification)
    {
        $this->authorizeNotification($notification);
        $notification->markAsRead();

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json(['ok' => true]);
        }

        if ($notification->action_url) {
            return redirect($notification->action_url);
        }

        $data = is_array($notification->data) ? $notification->data : [];
        if (! empty($data['delete_request_id'])) {
            return redirect()->route('settings-panel.delete-requests.show', $data['delete_request_id']);
        }

        return back();
    }

    public function markAllRead(string $company_slug)
    {
        UserNotification::query()
            ->where('user_id', Auth::id())
            ->unread()
            ->update(['read_at' => now()]);

        if (request()->wantsJson() || request()->ajax()) {
            return response()->json(['ok' => true]);
        }

        return back()->with('success', __('All notifications marked as read.'));
    }

    public function dismiss(string $company_slug, UserNotification $notification)
    {
        $this->authorizeNotification($notification);
        $notification->dismiss();

        if (request()->wantsJson() || request()->ajax()) {
            return response()->json(['ok' => true]);
        }

        return back();
    }

    private function authorizeNotification(UserNotification $notification): void
    {
        if ((int) $notification->user_id !== (int) Auth::id()) {
            abort(403);
        }
    }

    private function serialize(UserNotification $n): array
    {
        $data = is_array($n->data) ? $n->data : [];
        $actionMode = $data['action_mode'] ?? null;
        if ($actionMode === null && is_string($n->type) && str_starts_with($n->type, 'cheques.')) {
            $actionMode = 'modal';
        }
        $actionMode = $actionMode ?: 'navigate';

        $actionUrl = $n->action_url;
        if (is_string($actionUrl) && $actionUrl !== '' && ! str_starts_with($actionUrl, 'http')) {
            $actionUrl = url($actionUrl);
        }

        return [
            'id' => $n->id,
            'type' => $n->type,
            'title' => $n->title,
            'body' => $n->body,
            'severity' => $n->severity ?? 'info',
            'action_url' => $actionUrl,
            'action_mode' => $actionMode,
            'action_size' => $data['action_size'] ?? ($actionMode === 'modal' ? 'xl' : null),
            'action_title' => $data['action_title'] ?? ($actionMode === 'modal' ? ($n->title ?: 'Details') : null),
            'source_type' => $n->source_type,
            'source_id' => $n->source_id,
            'read_at' => $n->read_at?->toIso8601String(),
            'created_at' => $n->created_at?->toIso8601String(),
            'is_unread' => $n->isUnread(),
            'mark_read_url' => route('notifications.read', $n),
        ];
    }
}

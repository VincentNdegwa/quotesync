<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;
use Inertia\Inertia;

class NotificationController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        abort_unless($user !== null, 403);

        return response()->json([
            'unread_count' => $user->unreadNotifications()->count(),
            'items' => $user->notifications()
                ->latest()
                ->limit(10)
                ->get()
                ->map(fn (DatabaseNotification $notification): array => [
                    'id' => $notification->id,
                    'kind' => (string) ($notification->data['kind'] ?? 'system'),
                    'icon' => (string) ($notification->data['icon'] ?? 'bell'),
                    'title' => (string) ($notification->data['title'] ?? __('Notification')),
                    'message' => (string) ($notification->data['message'] ?? ''),
                    'url' => (string) ($notification->data['url'] ?? route('dashboard')),
                    'is_read' => $notification->read_at !== null,
                    'created_at' => $notification->created_at?->toIso8601String(),
                    'time_ago' => $notification->created_at?->diffForHumans(),
                ])
                ->values(),
        ]);
    }

    public function markAllRead(Request $request): RedirectResponse
    {
        $request->user()?->unreadNotifications()->update(['read_at' => now()]);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Notifications marked as read.'),
        ]);

        return back();
    }

    public function markRead(Request $request, string $notification): RedirectResponse
    {
        $record = $request->user()?->notifications()->whereKey($notification)->firstOrFail();

        if ($record->read_at === null) {
            $record->markAsRead();
        }

        $redirectTo = (string) $request->input('redirect_to', route('dashboard'));

        if (! str_starts_with($redirectTo, '/')) {
            $redirectTo = route('dashboard');
        }

        return redirect()->to($redirectTo);
    }

    public function markAsRead(Request $request, string $notification): RedirectResponse
    {
        return $this->markRead($request, $notification);
    }
}

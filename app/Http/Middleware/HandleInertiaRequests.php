<?php

namespace App\Http\Middleware;

use App\Models\Workspace;
use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $user = $request->user();

        return [
            ...parent::share($request),
            'name' => config('app.name'),
            'brand' => config('app.brand'),
            'auth' => [
                'user' => $user,
                'currentWorkspace' => $user?->currentWorkspace
                    ? [
                        'id' => $user->currentWorkspace->id,
                        'name' => $user->currentWorkspace->name,
                        'display_name' => $user->currentWorkspace->display_name,
                    ]
                    : null,
                'workspaces' => $user
                    ? $user->workspaces()
                        ->orderByRaw('LOWER(workspaces.name)')
                        ->get(['workspaces.id', 'workspaces.name', 'workspaces.display_name', 'workspaces.owner_id'])
                        ->map(fn (Workspace $workspace): array => [
                            'id' => $workspace->id,
                            'name' => $workspace->name,
                            'display_name' => $workspace->display_name,
                            'is_owner' => $workspace->owner_id === $user->id,
                        ])
                        ->values()
                    : [],
            ],
            'notifications' => fn (): array => $user
                ? [
                    'unread_count' => $user->unreadNotifications()->count(),
                    'items' => $user->notifications()
                        ->latest()
                        ->limit(10)
                        ->get()
                        ->map(fn (DatabaseNotification $notification): array => $this->notificationPayload($notification))
                        ->values(),
                ]
                : [
                    'unread_count' => 0,
                    'items' => [],
                ],
            'sidebarOpen' => ! $request->hasCookie('sidebar_state') || $request->cookie('sidebar_state') === 'true',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function notificationPayload(DatabaseNotification $notification): array
    {
        $kind = (string) ($notification->data['kind'] ?? 'system');

        return [
            'id' => $notification->id,
            'kind' => $kind,
            'icon' => (string) ($notification->data['icon'] ?? $this->iconForKind($kind)),
            'title' => (string) ($notification->data['title'] ?? __('Notification')),
            'message' => (string) ($notification->data['message'] ?? ''),
            'url' => (string) ($notification->data['url'] ?? route('dashboard')),
            'is_read' => $notification->read_at !== null,
            'created_at' => $notification->created_at?->toIso8601String(),
            'time_ago' => $notification->created_at?->diffForHumans(),
        ];
    }

    private function iconForKind(string $kind): string
    {
        return match ($kind) {
            'quote.viewed' => 'eye',
            'quote.accepted' => 'circle-check-big',
            'quote.declined' => 'circle-x',
            'quote.expired' => 'clock-3',
            'quote.sent' => 'send',
            default => 'bell',
        };
    }
}

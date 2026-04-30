<?php

namespace App\Http\Middleware;

use App\Enums\FollowUpChannel;
use App\Enums\InvoiceStatus;
use App\Enums\QuoteActivityType;
use App\Enums\QuoteFollowUpStatus;
use App\Enums\QuoteStatus;
use App\Enums\SignalDirection;
use App\Enums\TrackingEventType;
use App\Enums\WinProbabilityConfidence;
use App\Models\PortalInvitation;
use App\Models\Workspace;
use App\Services\WhiteLabelService;
use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Facades\Auth;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    public function __construct(
        private WhiteLabelService $whiteLabelService
    ) {}

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
     * Determines if the request should be handled by Inertia.
     */
    public function handle(Request $request, \Closure $next)
    {
        // Exclude tracking endpoint from Inertia
        if ($request->is('q/*/tracking')) {
            return $next($request);
        }

        return parent::handle($request, $next);
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
        $whiteLabel = $this->whiteLabelService->getBrandingForRequest($request);

        // Check if user is authenticated via portal guard
        $portalUser = Auth::guard('portal')->user();
        $isPortalUser = $portalUser !== null;

        // Use portal user if authenticated via portal guard
        if ($isPortalUser) {
            $user = $portalUser;
        }

        // Get workspace currency for authenticated users
        $workspaceCurrency = 'USD';
        if ($user) {
            $workspace = $isPortalUser
                ? Workspace::find($request->session()->get('portal_current_workspace_id') ?? $user->workspace_id)
                : $user->currentWorkspace;

            if ($workspace) {
                $workspaceCurrency = $workspace->settings()
                    ->where('group', 'quotes')
                    ->where('key', 'default_currency')
                    ->value('value') ?? 'USD';
            }
        }

        return [
            ...parent::share($request),
            'name' => $whiteLabel['enabled'] ? $whiteLabel['company_name'] : config('app.name'),
            'brand' => config('app.brand'),
            'whiteLabel' => $whiteLabel,
            'workspace_currency' => $workspaceCurrency,
            'auth' => [
                'user' => $user,
                'portal_user' => $isPortalUser ? $user : null,
                'currentWorkspace' => $isPortalUser && $user
                    ? (function () use ($request, $user) {
                        $sessionWorkspaceId = $request->session()->get('portal_current_workspace_id');
                        $workspaceId = $sessionWorkspaceId ?? $user->workspace_id;
                        $workspace = Workspace::find($workspaceId);

                        if (! $workspace) {
                            $workspace = $user->workspace;
                        }

                        return $workspace ? [
                            'id' => $workspace->id,
                            'name' => $workspace->name,
                            'display_name' => $workspace->display_name,
                        ] : null;
                    })()
                    : (! $isPortalUser && $user?->currentWorkspace
                        ? [
                            'id' => $user->currentWorkspace->id,
                            'name' => $user->currentWorkspace->name,
                            'display_name' => $user->currentWorkspace->display_name,
                        ]
                        : null),
                'workspaces' => $isPortalUser
                    ? ($user
                        ? PortalInvitation::where('email', $user->email)
                            ->whereNotNull('accepted_at')
                            ->with('workspace')
                            ->get()
                            ->pluck('workspace')
                            ->unique('id')
                            ->sortBy('name')
                            ->map(fn (Workspace $workspace): array => [
                                'id' => $workspace->id,
                                'name' => $workspace->name,
                                'display_name' => $workspace->display_name,
                                'logo' => $workspace->white_label_enabled ? $workspace->white_label_logo : null,
                                'company_name' => $workspace->white_label_enabled ? $workspace->white_label_company_name : $workspace->name,
                            ])
                            ->values()
                        : [])
                    : ($user
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
                        : []),
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
            'enums' => [
                'quoteStatus' => QuoteStatus::all(),
                'quoteActivityType' => QuoteActivityType::all(),
                'followUpChannel' => FollowUpChannel::all(),
                'quoteFollowUpStatus' => QuoteFollowUpStatus::all(),
                'trackingEventType' => TrackingEventType::all(),
                'invoiceStatus' => InvoiceStatus::all(),
                'winProbabilityConfidence' => WinProbabilityConfidence::all(),
                'signalDirection' => SignalDirection::all(),
            ],
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

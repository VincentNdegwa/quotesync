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
use App\Services\ApprovalService;
use App\Services\WhiteLabelService;
use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Facades\Auth;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    public function __construct(
        private WhiteLabelService $whiteLabelService,
        private ApprovalService $approvalService,
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
        $user = $this->getAuthenticatedUser($request);
        $whiteLabel = $this->whiteLabelService->getBrandingForRequest($request);
        $workspace = $this->getWorkspace($request, $user);
        $isPortalUser = Auth::guard('portal')->user() !== null;

        return [
            ...parent::share($request),
            'name' => $whiteLabel['enabled'] ? $whiteLabel['company_name'] : config('app.name'),
            'brand' => config('app.brand'),
            'whiteLabel' => $whiteLabel,
            'workspace_currency' => $this->getWorkspaceCurrency($workspace),
            'pending_approvals_count' => $user && $workspace ? $this->approvalService->count($workspace, $user) : 0,
            'auth' => [
                'user' => $user,
                'portal_user' => $isPortalUser ? $user : null,
                'currentWorkspace' => $this->getCurrentWorkspacePayload($workspace),
                'workspaces' => $this->getWorkspacesPayload($request, $user, $isPortalUser),
            ],
            'notifications' => $this->getNotificationsPayload($user),
            'sidebarOpen' => ! $request->hasCookie('sidebar_state') || $request->cookie('sidebar_state') === 'true',
            'enums' => $this->getEnumsPayload(),
        ];
    }

    /**
     * Get the authenticated user (regular or portal).
     */
    private function getAuthenticatedUser(Request $request): mixed
    {
        $portalUser = Auth::guard('portal')->user();
        return $portalUser ?? $request->user();
    }

    /**
     * Get the current workspace.
     */
    private function getWorkspace(Request $request, mixed $user): ?Workspace
    {
        if (! $user) {
            return null;
        }

        $isPortalUser = Auth::guard('portal')->user() !== null;

        if ($isPortalUser) {
            $sessionWorkspaceId = $request->session()->get('portal_current_workspace_id');
            $workspaceId = $sessionWorkspaceId ?? $user->workspace_id;
            $workspace = Workspace::find($workspaceId);

            return $workspace ?? $user->workspace;
        }

        return $user->currentWorkspace;
    }

    /**
     * Get the workspace currency.
     */
    private function getWorkspaceCurrency(?Workspace $workspace): string
    {
        if (! $workspace) {
            return 'USD';
        }

        return $workspace->currency ?? 'USD';
    }

    /**
     * Get the current workspace payload for the frontend.
     */
    private function getCurrentWorkspacePayload(?Workspace $workspace): ?array
    {
        if (! $workspace) {
            return null;
        }

        return [
            'id' => $workspace->id,
            'name' => $workspace->name,
            'display_name' => $workspace->display_name,
        ];
    }

    /**
     * Get the workspaces payload for the frontend.
     *
     * @return array<int, array<string, mixed>>
     */
    private function getWorkspacesPayload(Request $request, mixed $user, bool $isPortalUser): array
    {
        if (! $user) {
            return [];
        }

        if ($isPortalUser) {
            return $this->getPortalWorkspaces($user);
        }

        return $this->getUserWorkspaces($user);
    }

    /**
     * Get workspaces for portal users.
     *
     * @return array<int, array<string, mixed>>
     */
    private function getPortalWorkspaces(mixed $user): array
    {
        return PortalInvitation::where('email', $user->email)
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
                'logo' => $workspace->white_label_mode ? $workspace->logo_path : null,
                'company_name' => $workspace->white_label_mode ? $workspace->name : $workspace->name,
            ])
            ->values()
            ->all();
    }

    /**
     * Get workspaces for regular users.
     *
     * @return array<int, array<string, mixed>>
     */
    private function getUserWorkspaces(mixed $user): array
    {
        return $user->workspaces()
            ->orderByRaw('LOWER(workspaces.name)')
            ->get(['workspaces.id', 'workspaces.name', 'workspaces.display_name', 'workspaces.owner_id'])
            ->map(fn (Workspace $workspace): array => [
                'id' => $workspace->id,
                'name' => $workspace->name,
                'display_name' => $workspace->display_name,
                'is_owner' => $workspace->owner_id === $user->id,
            ])
            ->values()
            ->all();
    }

    /**
     * Get the notifications payload.
     *
     * @return array<string, mixed>
     */
    private function getNotificationsPayload(mixed $user): array
    {
        if (! $user) {
            return [
                'unread_count' => 0,
                'items' => [],
            ];
        }

        return [
            'unread_count' => $user->unreadNotifications()->count(),
            'items' => $user->notifications()
                ->latest()
                ->limit(10)
                ->get()
                ->map(fn (DatabaseNotification $notification): array => $this->notificationPayload($notification))
                ->values()
                ->all(),
        ];
    }

    /**
     * Get the enums payload.
     *
     * @return array<string, mixed>
     */
    private function getEnumsPayload(): array
    {
        return [
            'quoteStatus' => QuoteStatus::all(),
            'quoteActivityType' => QuoteActivityType::all(),
            'followUpChannel' => FollowUpChannel::all(),
            'quoteFollowUpStatus' => QuoteFollowUpStatus::all(),
            'trackingEventType' => TrackingEventType::all(),
            'invoiceStatus' => InvoiceStatus::all(),
            'winProbabilityConfidence' => WinProbabilityConfidence::all(),
            'signalDirection' => SignalDirection::all(),
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

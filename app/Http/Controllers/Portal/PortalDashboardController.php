<?php

namespace App\Http\Controllers\Portal;

use App\Models\Quote;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class PortalDashboardController
{
    public function index(Request $request): Response
    {
        $portalUser = Auth::guard('portal')->user();
        abort_unless($portalUser, 401);

        $workspaceId = $request->attributes->get('portal_workspace_id');
        $clientId = $request->attributes->get('portal_client_id');

        $quotes = Quote::where('client_id', $clientId)
            ->where('workspace_id', $workspaceId)
            ->with(['workspace', 'template'])
            ->orderBy('created_at', 'desc')
            ->get();

        $stats = [
            'total' => $quotes->count(),
            'pending' => $quotes->where('status', 'sent')->count(),
            'viewed' => $quotes->where('status', 'viewed')->count(),
            'accepted' => $quotes->where('status', 'accepted')->count(),
            'declined' => $quotes->where('status', 'declined')->count(),
        ];

        return Inertia::render('portal/Dashboard', [
            'quotes' => $quotes,
            'stats' => $stats,
        ])->withViewData('title', 'Client Portal');
    }

    public function quotes(Request $request): Response
    {
        $portalUser = Auth::guard('portal')->user();
        abort_unless($portalUser, 401);

        $workspaceId = $request->attributes->get('portal_workspace_id');
        $clientId = $request->attributes->get('portal_client_id');

        $search = $request->get('search', '');
        $status = $request->get('status', '');
        $sort = $request->get('sort', 'newest');

        $query = Quote::where('client_id', $clientId)
            ->where('workspace_id', $workspaceId)
            ->where('status', '!=', 'draft')
            ->with(['workspace', 'template']);

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('quote_number', 'like', "%{$search}%")
                    ->orWhere('title', 'like', "%{$search}%");
            });
        }

        // Map client-facing status filter to internal statuses
        $statusMap = [
            'sent' => ['sent', 'viewed', 'pending_approval', 'expired'],
            'accepted' => ['accepted', 'won'],
            'rejected' => ['declined', 'lost'],
        ];

        if ($status && isset($statusMap[$status])) {
            $query->whereIn('status', $statusMap[$status]);
        }

        match ($sort) {
            'number' => $query->orderBy('quote_number'),
            'amount' => $query->orderBy('total', 'desc'),
            'valid_until' => $query->orderBy('valid_until'),
            default => $query->orderBy('created_at', 'desc'),
        };

        $quotes = $query->paginate(15);

        // Map internal statuses to client-facing statuses
        $quotes->getCollection()->transform(function ($quote) {
            $quote->client_status = match ($quote->status) {
                'sent', 'viewed', 'pending_approval', 'expired' => 'sent',
                'accepted', 'won' => 'accepted',
                'declined', 'lost' => 'rejected',
                default => $quote->status,
            };
            return $quote;
        });

        return Inertia::render('portal/Quotes', [
            'quotes' => $quotes,
            'filters' => [
                'search' => $search,
                'status' => $status,
                'sort' => $sort,
            ],
        ])->withViewData('title', 'Quotes');
    }

    public function show(Request $request, string $uuid): Response
    {
        $portalUser = Auth::guard('portal')->user();
        abort_unless($portalUser, 401);

        $workspaceId = $request->attributes->get('portal_workspace_id');
        $clientId = $request->attributes->get('portal_client_id');

        $quote = Quote::where('quote_uuid', $uuid)
            ->where('client_id', $clientId)
            ->where('workspace_id', $workspaceId)
            ->with([
                'workspace',
                'template',
                'client',
                'sections.lineItems.catalogItem',
                'sections.lineItems.taxes',
            ])
            ->firstOrFail();

        $quote->loadMissing(['client:id,company_name,contact_name,email', 'workspace:id,name,display_name']);
        $quote->append('signature_url');

        // Track view status like public view
        $wasFirstView = $quote->viewed_at === null;
        $newStatus = $quote->status === \App\Enums\QuoteStatus::Sent ? \App\Enums\QuoteStatus::Viewed->value : $quote->status->value;

        $quote->forceFill([
            'status' => $newStatus,
            'viewed_at' => $quote->viewed_at ?? now(),
            'view_count' => max(0, (int) $quote->view_count) + 1,
        ])->save();

        \App\Events\QuoteViewed::dispatch($quote);

        \App\Models\QuoteActivity::query()->create([
            'quote_id' => $quote->id,
            'workspace_id' => $quote->workspace_id,
            'user_id' => null,
            'type' => 'viewed',
            'description' => $wasFirstView ? 'Quote viewed for the first time.' : 'Quote viewed again by client.',
            'metadata' => [
                'first_view' => $wasFirstView,
                'view_count' => (int) $quote->view_count,
            ],
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        $layout = $quote->layout_snapshot ?? $quote->template?->layout ?? null;

        $branding = [
            'company_name' => $quote->workspace->white_label_enabled
                ? $quote->workspace->white_label_company_name
                : $quote->workspace->name,
            'logo_url' => $quote->workspace->white_label_enabled
                ? $quote->workspace->white_label_logo
                : null,
            'primary_color' => $quote->workspace->white_label_enabled
                ? ($quote->workspace->white_label_primary_color ?? '#2563EB')
                : '#2563EB',
            'accent_color' => '#F59E0B',
            'company_email' => $quote->workspace->owner?->email,
            'company_phone' => null,
        ];

        return Inertia::render('portal/QuoteShow', [
            'quote' => $quote->makeHidden(['internal_notes', 'profit_margin', 'deleted_at']),
            'layout' => $layout,
            'branding' => $branding,
        ])->withViewData('title', 'Quote Details');
    }

    public function approve(Request $request, int $id): RedirectResponse
    {
        $portalUser = Auth::guard('portal')->user();
        abort_unless($portalUser, 401);

        $workspaceId = $request->attributes->get('portal_workspace_id');
        $clientId = $request->attributes->get('portal_client_id');

        $quote = Quote::where('id', $id)
            ->where('client_id', $clientId)
            ->where('workspace_id', $workspaceId)
            ->firstOrFail();

        $quote->update(['status' => 'accepted']);

        return redirect()->back();
    }

    public function reject(Request $request, int $id): RedirectResponse
    {
        $portalUser = Auth::guard('portal')->user();
        abort_unless($portalUser, 401);

        $workspaceId = $request->attributes->get('portal_workspace_id');
        $clientId = $request->attributes->get('portal_client_id');

        $quote = Quote::where('id', $id)
            ->where('client_id', $clientId)
            ->where('workspace_id', $workspaceId)
            ->firstOrFail();

        $quote->update(['status' => 'declined']);

        return redirect()->back();
    }
}

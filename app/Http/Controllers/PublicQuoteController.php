<?php

namespace App\Http\Controllers;

use App\Enums\QuoteFollowUpStatus;
use App\Enums\QuoteStatus;
use App\Models\Quote;
use App\Models\QuoteActivity;
use App\Models\Workspace;
use App\Notifications\QuoteViewedNotification;
use App\Services\Quotes\QuoteShortCodeService;
use App\Services\WorkspaceSettings\WorkspaceSettingsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class PublicQuoteController extends Controller
{
    public function show(
        string $quoteUuid,
        Request $request,
        WorkspaceSettingsService $workspaceSettingsService,
        QuoteShortCodeService $quoteShortCodeService,
    ): Response
    {
        $resolvedQuote = $quoteShortCodeService->resolveQuoteByIdentifier($quoteUuid);

        abort_unless($resolvedQuote instanceof Quote, 404);

        $quote = Quote::query()
            ->with([
                'client',
                'workspace',
                'template',
                'creator:id,name,email',
                'assignee:id,name,email',
                'sections.lineItems.catalogItem',
                'sections.lineItems.taxes',
            ])
            ->whereKey($resolvedQuote->id)
            ->firstOrFail();

        $quote->loadMissing(['client:id,company_name,contact_name,email', 'workspace:id,name,display_name']);
        $quote->append('signature_url');

        $wasFirstView = $quote->viewed_at === null;
        $newStatus = $quote->status === QuoteStatus::Sent ? QuoteStatus::Viewed->value : $quote->status->value;

        $quote->forceFill([
            'status' => $newStatus,
            'viewed_at' => $quote->viewed_at ?? now(),
            'view_count' => max(0, (int) $quote->view_count) + 1,
        ])->save();

        QuoteActivity::query()->create([
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

        if (! DatabaseNotification::query()
            ->where('type', QuoteViewedNotification::class)
            ->where('data->quote_id', $quote->id)
            ->where('created_at', '>=', now()->subHour())
            ->exists()) {
            Notification::send($this->quoteRecipients($quote), new QuoteViewedNotification($quote));
        }

        return Inertia::render('public/QuoteView', [
            'quote' => $quote->makeHidden(['internal_notes', 'profit_margin', 'deleted_at']),
            'quote_uuid' => $quote->quote_uuid,
            'layout' => $this->quoteLayoutPayload($quote),
            'branding' => $this->brandingPayload($quote->workspace, $workspaceSettingsService),
            'status' => $quote->status->value,
            'is_expired' => $quote->status === QuoteStatus::Expired,
        ]);
    }

    /**
     * @return array<string, mixed>|null
     */
    private function quoteLayoutPayload(Quote $quote): ?array
    {
        return $quote->layout_snapshot
            ?? $quote->template?->layout
            ?? null;
    }

    /**
     * @return array<string, mixed>
     */
    private function brandingPayload(Workspace $workspace, WorkspaceSettingsService $workspaceSettingsService): array
    {
        /** @var Collection<int, array<string, mixed>> $fields */
        $fields = collect($workspaceSettingsService->groupForFrontend($workspace, 'brand')['fields'] ?? []);
        $brandFields = $fields->keyBy('key');

        $logoPath = $brandFields->get('logo_path')['value'] ?? null;
        $logoUrl = is_string($logoPath) && $logoPath !== '' ? Storage::url($logoPath) : null;

        return [
            'company_name' => $brandFields->get('company_name')['value'] ?? $workspace->display_name ?? $workspace->name,
            'logo_url' => $logoUrl,
            'primary_color' => $brandFields->get('primary_color')['value'] ?? '#2563EB',
            'accent_color' => $brandFields->get('accent_color')['value'] ?? '#F59E0B',
            'company_email' => $brandFields->get('company_email')['value'] ?? null,
            'company_phone' => $brandFields->get('company_phone')['value'] ?? null,
            'company_address' => $brandFields->get('company_address')['value'] ?? null,
            'company_tagline' => $brandFields->get('company_tagline')['value'] ?? null,
        ];
    }

    /**
     * @return Collection<int, User>
     */
    private function quoteRecipients(Quote $quote): Collection
    {
        return collect([$quote->creator, $quote->assignee])
            ->filter()
            ->unique('id')
            ->values();
    }

    public function accept(string $quoteUuid, Request $request, QuoteShortCodeService $quoteShortCodeService): RedirectResponse
    {
        $quote = $quoteShortCodeService->resolveQuoteByIdentifier($quoteUuid);

        abort_unless($quote instanceof Quote, 404);

        if (in_array($quote->status, [QuoteStatus::Accepted, QuoteStatus::Declined, QuoteStatus::Expired], true)) {
            return back()->with('error', 'This quote cannot be accepted.');
        }

        $validated = $request->validate([
            'signer_name' => ['nullable', 'string', 'max:255'],
            'signature' => ['required', 'string', 'starts_with:data:image/png;base64,'],
        ]);

        $signatureData = substr($validated['signature'], strpos($validated['signature'], ',') + 1);
        $signatureData = base64_decode($signatureData);

        $quote->forceFill([
            'status' => 'accepted',
            'accepted_at' => now(),
            'signature' => $signatureData,
            'signer_name' => $validated['signer_name'] ?? null,
        ])->save();

        QuoteActivity::query()->create([
            'quote_id' => $quote->id,
            'workspace_id' => $quote->workspace_id,
            'user_id' => null,
            'type' => 'status_changed',
            'description' => 'Quote was accepted and signed by '.$validated['signer_name'].'.',
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'metadata' => ['status' => 'accepted', 'signer_name' => $validated['signer_name'] ?? null],
        ]);

        $quote->quoteFollowUps()
            ->where('status', QuoteFollowUpStatus::Pending->value)
            ->update([
                'status' => QuoteFollowUpStatus::Cancelled->value,
                'cancelled_at' => now(),
            ]);

        return back()->with('success', 'Quote has been successfully accepted.');
    }

    public function decline(string $quoteUuid, Request $request, QuoteShortCodeService $quoteShortCodeService): RedirectResponse
    {
        $quote = $quoteShortCodeService->resolveQuoteByIdentifier($quoteUuid);

        abort_unless($quote instanceof Quote, 404);

        if (in_array($quote->status, [QuoteStatus::Accepted, QuoteStatus::Declined, QuoteStatus::Expired], true)) {
            return back()->with('error', 'This quote cannot be declined.');
        }

        $validated = $request->validate([
            'decline_reason' => ['nullable', 'string', 'max:1000'],
        ]);

        $quote->forceFill([
            'status' => 'declined',
            'declined_at' => now(),
            'decline_reason' => $validated['decline_reason'] ?? null,
        ])->save();

        QuoteActivity::query()->create([
            'quote_id' => $quote->id,
            'workspace_id' => $quote->workspace_id,
            'user_id' => null,
            'type' => 'status_changed',
            'description' => 'Quote was declined by the client.',
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'metadata' => ['status' => 'declined', 'reason' => $validated['decline_reason'] ?? null],
        ]);

        $quote->quoteFollowUps()
            ->where('status', QuoteFollowUpStatus::Pending->value)
            ->update([
                'status' => QuoteFollowUpStatus::Cancelled->value,
                'cancelled_at' => now(),
            ]);

        return back()->with('success', 'Quote has been declined.');
    }
}

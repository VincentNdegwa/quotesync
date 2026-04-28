<?php

namespace App\Http\Controllers;

use App\Enums\QuoteActivityType;
use App\Enums\QuoteFollowUpStatus;
use App\Enums\QuoteStatus;
use App\Events\QuoteViewed;
use App\Models\Quote;
use App\Models\QuoteActivity;
use App\Models\Workspace;
use App\Notifications\QuoteAcceptedNotification;
use App\Notifications\QuoteDeclinedNotification;
use App\Notifications\QuoteViewedNotification;
use App\Services\Quotes\QuoteShortCodeService;
use App\Services\WorkspaceSettings\WorkspaceSettingsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
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

        QuoteViewed::dispatch($quote);

        QuoteActivity::query()->create([
            'quote_id' => $quote->id,
            'workspace_id' => $quote->workspace_id,
            'user_id' => null,
            'type' => QuoteActivityType::Viewed->value,
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

        $signaturePayload = substr($validated['signature'], strpos($validated['signature'], ',') + 1);
        $signatureBinary = base64_decode($signaturePayload, true);

        if ($signatureBinary === false) {
            throw ValidationException::withMessages([
                'signature' => 'The provided signature image is invalid.',
            ]);
        }

        $signaturePath = sprintf('signatures/%s-%s.png', $quote->quote_uuid, Str::uuid());

        if (is_string($quote->signature_path) && $quote->signature_path !== '') {
            Storage::disk('public')->delete($quote->signature_path);
        }

        Storage::disk('public')->put($signaturePath, $signatureBinary);

        $quote->forceFill([
            'status' => QuoteStatus::Accepted->value,
            'accepted_at' => now(),
            'signature_path' => $signaturePath,
            'signer_name' => $validated['signer_name'] ?? null,
            'signer_ip' => $request->ip(),
        ])->save();

        QuoteActivity::query()->create([
            'quote_id' => $quote->id,
            'workspace_id' => $quote->workspace_id,
            'user_id' => null,
            'type' => QuoteActivityType::Accepted->value,
            'description' => $validated['signer_name']
                ? 'Quote was accepted and signed by '.$validated['signer_name'].'.'
                : 'Quote was accepted and signed by the client.',
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'metadata' => [
                'status' => 'accepted',
                'signer_name' => $validated['signer_name'] ?? null,
                'signature_path' => $signaturePath,
            ],
        ]);

        Notification::send(
            $this->quoteRecipients($quote),
            new QuoteAcceptedNotification($quote),
        );

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
            'status' => QuoteStatus::Declined->value,
            'declined_at' => now(),
            'decline_reason' => $validated['decline_reason'] ?? null,
        ])->save();

        QuoteActivity::query()->create([
            'quote_id' => $quote->id,
            'workspace_id' => $quote->workspace_id,
            'user_id' => null,
            'type' => QuoteActivityType::Declined->value,
            'description' => 'Quote was declined by the client.',
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'metadata' => ['status' => 'declined', 'reason' => $validated['decline_reason'] ?? null],
        ]);

        Notification::send(
            $this->quoteRecipients($quote),
            new QuoteDeclinedNotification(
                quote: $quote,
                reason: $validated['decline_reason'] ?? null,
            ),
        );

        $quote->quoteFollowUps()
            ->where('status', QuoteFollowUpStatus::Pending->value)
            ->update([
                'status' => QuoteFollowUpStatus::Cancelled->value,
                'cancelled_at' => now(),
            ]);

        return back()->with('success', 'Quote has been declined.');
    }
}

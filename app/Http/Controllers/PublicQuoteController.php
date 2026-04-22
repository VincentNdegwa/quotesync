<?php

namespace App\Http\Controllers;

use App\Models\Quote;
use App\Models\QuoteActivity;
use App\Models\User;
use App\Models\Workspace;
use App\Notifications\QuoteViewedNotification;
use App\Services\WorkspaceSettings\WorkspaceSettingsService;
use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class PublicQuoteController extends Controller
{
    public function show(string $quoteUuid, Request $request, WorkspaceSettingsService $workspaceSettingsService): Response
    {
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
            ->where('quote_uuid', $quoteUuid)
            ->firstOrFail();

        $quote->loadMissing(['client:id,company_name,contact_name,email', 'workspace:id,name,display_name']);

        $wasFirstView = $quote->viewed_at === null;
        $newStatus = $quote->status === 'sent' ? 'viewed' : $quote->status;

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
            'quote' => $this->quoteRendererPayload($quote),
            'layout' => $this->quoteLayoutPayload($quote),
            'branding' => $this->brandingPayload($quote->workspace, $workspaceSettingsService),
            'status' => $quote->status,
            'is_expired' => $quote->status === 'expired',
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function quoteRendererPayload(Quote $quote): array
    {
        return [
            'id' => $quote->id,
            'number' => $quote->number,
            'title' => $quote->title,
            'client' => [
                'id' => $quote->client?->id,
                'companyName' => $quote->client?->company_name,
                'address' => $quote->client?->address,
            ],
            'createdAt' => $quote->created_at?->toISOString(),
            'validUntil' => $quote->valid_until?->toDateString(),
            'currency' => $quote->currency,
            'coverMessage' => $quote->cover_message,
            'terms' => $quote->terms,
            'subtotal' => (float) $quote->subtotal,
            'discountAmount' => (float) $quote->discount_amount,
            'taxAmount' => (float) $quote->tax_amount,
            'total' => (float) $quote->total,
            'sections' => $quote->sections->map(function ($section): array {
                return [
                    'id' => $section->id,
                    'title' => $section->title,
                    'lineItems' => $section->lineItems->map(function ($lineItem): array {
                        return [
                            'id' => $lineItem->id,
                            'name' => $lineItem->name,
                            'description' => $lineItem->description,
                            'quantity' => (float) $lineItem->quantity,
                            'unit' => $lineItem->unit,
                            'sku' => $lineItem->catalogItem?->sku,
                            'taxes' => $lineItem->taxes->map(fn ($tax): array => [
                                'taxId' => $tax->tax_id,
                                'taxLabel' => $tax->tax_label,
                                'taxRate' => (float) $tax->tax_rate,
                            ])->values()->all(),
                            'unitPrice' => (float) $lineItem->unit_price,
                            'discountPercent' => (float) $lineItem->discount_percent,
                            'taxAmount' => (float) $lineItem->tax_amount,
                            'total' => (float) $lineItem->total,
                            'isOptional' => (bool) $lineItem->is_optional,
                        ];
                    })->values()->all(),
                ];
            })->values()->all(),
        ];
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
            'companyName' => $brandFields->get('company_name')['value'] ?? $workspace->display_name ?? $workspace->name,
            'logoUrl' => $logoUrl,
            'primaryColor' => $brandFields->get('primary_color')['value'] ?? '#2563EB',
            'accentColor' => $brandFields->get('accent_color')['value'] ?? '#F59E0B',
            'companyEmail' => $brandFields->get('company_email')['value'] ?? null,
            'companyPhone' => $brandFields->get('company_phone')['value'] ?? null,
            'companyAddress' => $brandFields->get('company_address')['value'] ?? null,
            'companyTagline' => $brandFields->get('company_tagline')['value'] ?? null,
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
}

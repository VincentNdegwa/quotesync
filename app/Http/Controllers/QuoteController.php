<?php

namespace App\Http\Controllers;

use App\Enums\QuoteStatus;
use App\Http\Requests\StoreQuoteRequest;
use App\Http\Requests\UpdateQuoteRequest;
use App\Http\Requests\UpdateQuoteStatusRequest;
use App\Models\CatalogItem;
use App\Models\Client;
use App\Models\Quote;
use App\Models\QuoteTemplate;
use App\Models\Tax;
use App\Models\Workspace;
use App\Services\Quotes\QuoteService;
use App\Services\WorkspaceSettings\WorkspaceSettingsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class QuoteController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request, QuoteService $quoteService, WorkspaceSettingsService $workspaceSettingsService): Response
    {
        $workspace = $request->user()?->currentWorkspace;

        abort_unless($workspace instanceof Workspace, 404);

        $filters = [
            'search' => $request->string('search')->toString(),
            'status' => $request->string('status')->toString(),
            'sort' => $request->string('sort')->toString() ?: 'newest',
        ];

        return Inertia::render('quotes/Index', [
            'filters' => $filters,
            'quotes' => $quoteService->paginateForIndex($workspace, $filters)
                ->through(fn (Quote $quote): array => [
                    'id' => $quote->id,
                    'quote_uuid' => $quote->quote_uuid,
                    'number' => $quote->number,
                    'title' => $quote->title,
                    'status' => $quote->status,
                    'total' => (float) $quote->total,
                    'currency' => $quote->currency,
                    'valid_until' => $quote->valid_until?->toDateString(),
                    'created_at' => $quote->created_at?->toISOString(),
                    'client' => $quote->client ? [
                        'id' => $quote->client->id,
                        'company_name' => $quote->client->company_name,
                        'email' => $quote->client->email,
                    ] : null,
                    'assignee' => $quote->assignee ? [
                        'id' => $quote->assignee->id,
                        'name' => $quote->assignee->name,
                    ] : null,
                ]),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(Request $request, QuoteService $quoteService, WorkspaceSettingsService $workspaceSettingsService): Response
    {
        $workspace = $request->user()?->currentWorkspace;

        abort_unless($workspace instanceof Workspace, 404);

        $templateId = $request->integer('template_id');
        $template = null;

        if ($templateId > 0) {
            $template = QuoteTemplate::query()
                ->where('workspace_id', $workspace->id)
                ->where('is_active', true)
                ->find($templateId);
        }

        /** @var Collection<int, array<string, mixed>> $quoteFields */
        $quoteFields = collect($workspaceSettingsService->groupForFrontend($workspace, 'quotes')['fields'] ?? [])->keyBy('key');
        $defaultCurrency = (string) ($quoteFields->get('default_currency')['value'] ?? 'USD');
        $validityDays = max(1, (int) ($quoteFields->get('quote_validity_days')['value'] ?? 30));

        $initialState = [
            'id' => null,
            'number' => null,
            'title' => '',
            'status' => 'draft',
            'client_id' => null,
            'assigned_to' => $request->user()?->id,
            'currency' => $defaultCurrency,
            'valid_until' => now()->addDays($validityDays)->toDateString(),
            'cover_message' => null,
            'terms' => null,
            'notes' => null,
            'template_id' => $template?->id,
            'layout' => null,
            'layout_snapshot' => null,
            'requires_deposit' => false,
            'deposit_amount' => null,
            'subtotal' => 0,
            'discount_amount' => 0,
            'tax_amount' => 0,
            'total' => 0,
            'sections' => [
                [
                    'id' => null,
                    'title' => 'Services',
                    'sort_order' => 0,
                    'line_items' => [],
                ],
            ],
        ];

        if ($template instanceof QuoteTemplate) {
            $initialState = [
                ...$initialState,
                ...$quoteService->initialPayloadFromTemplate($template, $workspace),
            ];
        }

        return Inertia::render('quotes/Create', [
            'initialState' => $initialState,
            ...$this->builderLookups($workspace, $workspaceSettingsService),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreQuoteRequest $request, QuoteService $quoteService): RedirectResponse
    {
        $workspace = $request->user()?->currentWorkspace;

        abort_unless($workspace instanceof Workspace, 404);

        $payload = $request->validated();
        $payload['created_by'] = $request->user()?->id;

        $quote = $quoteService->create($workspace, $payload);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Quote draft created.')]);

        return to_route('quotes.edit', $quote);
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, Quote $quote, WorkspaceSettingsService $workspaceSettingsService): Response
    {
        $workspace = $request->user()?->currentWorkspace;

        abort_unless($workspace instanceof Workspace && $quote->workspace_id === $workspace->id, 404);

        $quote->loadMissing([
            'client:id,company_name,address',
            'workspace:id,name,display_name',
            'template:id,name,layout',
            'creator:id,name,email',
            'assignee:id,name,email',
            'sections.lineItems.catalogItem:id,sku',
            'sections.lineItems.taxes',
            'activities.user:id,name',
        ]);

        return Inertia::render('quotes/Show', [
            'quote' => $quote,
            'branding' => $this->brandingPayload($workspace, $workspaceSettingsService),
            'quoteStatuses' => QuoteStatus::all(),
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Request $request, Quote $quote, QuoteService $quoteService, WorkspaceSettingsService $workspaceSettingsService): Response
    {
        $workspace = $request->user()?->currentWorkspace;

        abort_unless($workspace instanceof Workspace && $quote->workspace_id === $workspace->id, 404);

        return Inertia::render('quotes/Edit', [
            'initialState' => $quoteService->toBuilderPayload($quote),
            ...$this->builderLookups($workspace, $workspaceSettingsService),
            'quoteId' => $quote->id,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateQuoteRequest $request, Quote $quote, QuoteService $quoteService): RedirectResponse
    {
        $workspace = $request->user()?->currentWorkspace;

        abort_unless($workspace instanceof Workspace && $quote->workspace_id === $workspace->id, 404);

        abort_unless($quote->status->canBeEdited(), 403, 'Only draft quotes can be edited.');

        $quoteService->update($quote, $request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Quote updated.')]);

        return back();
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, Quote $quote, QuoteService $quoteService): RedirectResponse
    {
        $workspace = $request->user()?->currentWorkspace;

        abort_unless($workspace instanceof Workspace && $quote->workspace_id === $workspace->id, 404);

        $quoteService->delete($quote);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Quote deleted.')]);

        return to_route('quotes.index');
    }

    /**
     * @return array<string, mixed>
     */
    private function builderLookups(Workspace $workspace, WorkspaceSettingsService $workspaceSettingsService): array
    {
        $branding = $this->brandingPayload($workspace, $workspaceSettingsService);

        return [
            'branding' => $branding,
            'clients' => Client::query()
                ->where('workspace_id', $workspace->id)
                ->orderByRaw('LOWER(company_name)')
                ->get(['id', 'company_name', 'currency'])
                ->map(fn (Client $client): array => [
                    'id' => $client->id,
                    'company_name' => $client->company_name,
                    'email' => $client->email,
                    'currency' => $client->currency,
                ])
                ->values(),
            'catalogItems' => CatalogItem::query()
                ->where('workspace_id', $workspace->id)
                ->where('is_active', true)
                ->with('taxes:id,name,rate')
                ->orderByRaw('LOWER(name)')
                ->limit(300)
                ->get(['id', 'name', 'description', 'sku', 'unit', 'unit_price'])
                ->map(fn (CatalogItem $item): array => [
                    'id' => $item->id,
                    'name' => $item->name,
                    'description' => $item->description,
                    'sku' => $item->sku,
                    'unit' => $item->unit,
                    'unit_price' => (float) $item->unit_price,
                    'taxes' => $item->taxes->map(fn (Tax $tax): array => [
                        'id' => $tax->id,
                        'name' => $tax->name,
                        'rate' => (float) $tax->rate,
                    ])->values()->all(),
                ])
                ->values(),
            'templates' => QuoteTemplate::query()
                ->where('workspace_id', $workspace->id)
                ->where('is_active', true)
                ->orderByDesc('is_system')
                ->orderByRaw('LOWER(name)')
                ->get(['id', 'name', 'description', 'is_system'])
                ->map(fn (QuoteTemplate $template): array => [
                    'id' => $template->id,
                    'name' => $template->name,
                    'description' => $template->description,
                    'is_system' => (bool) $template->is_system,
                ])
                ->values(),
            'taxes' => Tax::query()
                ->where('workspace_id', $workspace->id)
                ->where('is_active', true)
                ->orderByDesc('is_default')
                ->orderByRaw('LOWER(name)')
                ->get(['id', 'name', 'rate'])
                ->map(fn (Tax $tax): array => [
                    'id' => $tax->id,
                    'name' => $tax->name,
                    'rate' => (float) $tax->rate,
                ])
                ->values(),
        ];
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
            'company_name' => $brandFields->get('company_name')['value'] ?? null,
            'logo_url' => $logoUrl,
            'primary_color' => $brandFields->get('primary_color')['value'] ?? '#4F46E5',
            'accent_color' => $brandFields->get('accent_color')['value'] ?? '#F5A623',
            'company_email' => $brandFields->get('company_email')['value'] ?? null,
            'company_phone' => $brandFields->get('company_phone')['value'] ?? null,
            'company_address' => $brandFields->get('company_address')['value'] ?? null,
            'company_tagline' => $brandFields->get('company_tagline')['value'] ?? null,
        ];
    }

    public function updateStatus(UpdateQuoteStatusRequest $request, Quote $quote, QuoteService $quoteService): RedirectResponse
    {
        $workspace = $request->user()?->currentWorkspace;

        abort_unless($workspace instanceof Workspace && $quote->workspace_id === $workspace->id, 404);

        $status = $request->string('status')->toString();
        $newStatus = QuoteStatus::from($status);

        // Validate status transition
        $currentStatus = $quote->status;
        if (! in_array($newStatus, $currentStatus->allowedTransitions(), true)) {
            abort(403, "Invalid status transition from {$currentStatus->value} to {$newStatus->value}.");
        }

        if ($status === 'won') {
            $quoteService->markAsWon($quote);
            Inertia::flash('toast', ['type' => 'success', 'message' => __('Quote marked as won.')]);
        } elseif ($status === 'lost') {
            $reason = $request->string('reason')->toString();
            $quoteService->markAsLost($quote, $reason ?: null);
            Inertia::flash('toast', ['type' => 'success', 'message' => __('Quote marked as lost.')]);
        }

        return to_route('quotes.show', $quote);
    }

    public function duplicate(Request $request, Quote $quote, QuoteService $quoteService): RedirectResponse
    {
        $workspace = $request->user()?->currentWorkspace;

        abort_unless($workspace instanceof Workspace && $quote->workspace_id === $workspace->id, 404);

        $newQuote = $quoteService->duplicate($quote);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Quote duplicated successfully.')]);

        return to_route('quotes.edit', $newQuote);
    }

    public function revise(Request $request, Quote $quote, QuoteService $quoteService): RedirectResponse
    {
        $workspace = $request->user()?->currentWorkspace;

        abort_unless($workspace instanceof Workspace && $quote->workspace_id === $workspace->id, 404);

        $newQuote = $quoteService->revise($quote);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Quote revised successfully.')]);

        return to_route('quotes.edit', $newQuote);
    }

    public function reopen(Request $request, Quote $quote, QuoteService $quoteService): RedirectResponse
    {
        $workspace = $request->user()?->currentWorkspace;

        abort_unless($workspace instanceof Workspace && $quote->workspace_id === $workspace->id, 404);

        $validUntil = $request->string('valid_until')->toString() ?: now()->addDays(30)->toDateString();

        $quoteService->reopen($quote, $validUntil);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Quote reopened successfully.')]);

        return to_route('quotes.show', $quote);
    }

    public function archive(Request $request, Quote $quote, QuoteService $quoteService): RedirectResponse
    {
        $workspace = $request->user()?->currentWorkspace;

        abort_unless($workspace instanceof Workspace && $quote->workspace_id === $workspace->id, 404);

        $quoteService->archive($quote);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Quote archived successfully.')]);

        return to_route('quotes.index');
    }
}

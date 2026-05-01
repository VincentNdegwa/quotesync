<?php

namespace App\Http\Controllers;

use App\Enums\QuoteFollowUpStatus;
use App\Enums\QuoteStatus;
use App\Http\Requests\StoreQuoteRequest;
use App\Http\Requests\UpdateQuoteRequest;
use App\Http\Requests\UpdateQuoteStatusRequest;
use App\Jobs\SendFollowUpJob;
use App\Models\CatalogItem;
use App\Models\Client;
use App\Models\ConfigurationUnit;
use App\Models\Quote;
use App\Models\QuoteFollowUp;
use App\Models\QuoteTemplate;
use App\Models\Tax;
use App\Models\Workspace;
use App\Services\Quotes\QuoteAnalyticsService;
use App\Services\Quotes\QuoteService;
use App\Services\WorkspaceSettings\WorkspaceSettingsService;
use Illuminate\Http\JsonResponse;
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
                    'total' => (float) ($quote->base_total ?? $quote->total),
                    'base_total' => $quote->base_total ? (float) $quote->base_total : null,
                    'currency' => $quote->base_currency ?? $quote->currency,
                    'base_currency' => $quote->base_currency,
                    'valid_until' => $quote->valid_until?->toDateString(),
                    'created_at' => $quote->created_at?->toISOString(),
                    'win_probability' => $quote->winProbability?->toResponseArray(),
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

        $clientId = $request->integer('client_id');
        $client = null;

        if ($clientId > 0) {
            $client = Client::query()
                ->where('workspace_id', $workspace->id)
                ->find($clientId);
        }

        $settings = $workspaceSettingsService->builderSettings($workspace);
        $validityDays = max(1, $settings['quotes']['quote_validity_days']);

        $initialState = [
            'id' => null,
            'number' => null,
            'title' => '',
            'status' => 'draft',
            'client_id' => $client?->id,
            'assigned_to' => $request->user()?->id,
            'currency' => $settings['workspace']['currency'],
            'base_currency' => $settings['workspace']['currency'],
            'fx_rate' => null,
            'base_total' => null,
            'valid_until' => now()->addDays($validityDays)->toDateString(),
            'cover_message' => $settings['quotes']['default_cover_message'],
            'terms' => $settings['quotes']['default_terms'],
            'notes' => $settings['quotes']['default_notes'],
            'template_id' => $template?->id,
            'layout' => null,
            'layout_snapshot' => null,
            'requires_deposit' => $settings['quotes']['require_deposit'],
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
            'settings' => $settings,
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
            'quoteFollowUps.step:id,follow_up_sequence_id,channel,subject,message_template,day_offset',
            'winProbability.signals'
        ]);

        $quote = $this->transformForInternalView($quote);

        return Inertia::render('quotes/Show', [
            'quote' => $quote,
            'settings' => $workspaceSettingsService->builderSettings($workspace),
            'quoteStatuses' => QuoteStatus::all(),
        ]);
    }

    private function transformForInternalView(Quote $quote): array
    {
        $data = $quote->toArray();

        $data['subtotal'] = $quote->base_subtotal;
        $data['discount_amount'] = $quote->base_discount_amount;
        $data['tax_amount'] = $quote->base_tax_amount;
        $data['total'] = $quote->base_total;
        $data['currency'] = $quote->base_currency;

        if (isset($data['sections'])) {
            foreach ($data['sections'] as &$section) {
                if (isset($section['line_items'])) {
                    foreach ($section['line_items'] as &$lineItem) {
                        $lineItem['unit_price'] = $lineItem['base_unit_price'] ?? $lineItem['unit_price'];
                        $lineItem['subtotal'] = $lineItem['base_subtotal'] ?? $lineItem['subtotal'];
                        $lineItem['tax_amount'] = $lineItem['base_tax_amount'] ?? $lineItem['tax_amount'];
                        $lineItem['total'] = $lineItem['base_total'] ?? $lineItem['total'];

                        if (isset($lineItem['taxes'])) {
                            foreach ($lineItem['taxes'] as &$tax) {
                                $tax['tax_amount'] = $tax['base_tax_amount'] ?? $tax['tax_amount'];
                            }
                        }
                    }
                }
            }
        }

        return $data;
    }

    public function analytics(Request $request, Quote $quote, QuoteAnalyticsService $analyticsService): Response
    {
        $workspace = $request->user()?->currentWorkspace;

        abort_unless($workspace instanceof Workspace && $quote->workspace_id === $workspace->id, 404);

        $quote->loadMissing([
            'client:id,company_name,email',
            'assignee:id,name',
            'trackingEvents',
            'quoteFollowUps.step',
        ]);

        return Inertia::render('quotes/Analytics', [
            'quote' => [
                'id' => $quote->id,
                'quote_uuid' => $quote->quote_uuid,
                'number' => $quote->number,
                'title' => $quote->title,
                'status' => $quote->status instanceof QuoteStatus ? $quote->status->value : (string) $quote->status,
                'total' => (float) $quote->total,
                'currency' => $quote->currency,
                'valid_until' => $quote->valid_until?->toIso8601String(),
                'created_at' => $quote->created_at?->toIso8601String(),
                'client' => $quote->client ? [
                    'id' => $quote->client->id,
                    'company_name' => $quote->client->company_name,
                    'email' => $quote->client->email,
                ] : null,
                'assignee' => $quote->assignee ? [
                    'id' => $quote->assignee->id,
                    'name' => $quote->assignee->name,
                ] : null,
            ],
            'analytics' => $analyticsService->getAnalytics($quote),
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
            'settings' => $workspaceSettingsService->builderSettings($workspace),
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
        return [
            'clients' => Client::query()
                ->where('workspace_id', $workspace->id)
                ->orderByRaw('LOWER(company_name)')
                ->get(),
            'catalogItems' => CatalogItem::query()
                ->where('workspace_id', $workspace->id)
                ->where('is_active', true)
                ->with(['taxes', 'configurationUnit'])
                ->orderByRaw('LOWER(name)')
                ->limit(300)
                ->get(),
            'templates' => QuoteTemplate::query()
                ->where('workspace_id', $workspace->id)
                ->where('is_active', true)
                ->orderByDesc('is_system')
                ->orderByRaw('LOWER(name)')
                ->get(),
            'taxes' => Tax::query()
                ->where('workspace_id', $workspace->id)
                ->where('is_active', true)
                ->orderByDesc('is_default')
                ->orderByRaw('LOWER(name)')
                ->get(),
            'units' => ConfigurationUnit::query()
                ->where('workspace_id', $workspace->id)
                ->where('is_active', true)
                ->orderByRaw('LOWER(name)')
                ->get(),
        ];
    }


    public function kanban(Request $request, QuoteService $quoteService): JsonResponse
    {
        $workspace = $request->user()?->currentWorkspace;

        abort_unless($workspace instanceof Workspace, 404);

        return response()->json($quoteService->allForKanban($workspace));
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
        } else {
            $quote->update(['status' => $newStatus->value]);
            Inertia::flash('toast', ['type' => 'success', 'message' => __('Quote status updated.')]);
        }

        return back();
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

    public function cancelFollowUp(Request $request, Quote $quote, QuoteFollowUp $quoteFollowUp): RedirectResponse
    {
        $workspace = $request->user()?->currentWorkspace;

        abort_unless($workspace instanceof Workspace && $quote->workspace_id === $workspace->id, 404);
        abort_unless($quoteFollowUp->quote_id === $quote->id, 404);
        abort_unless($quoteFollowUp->status === QuoteFollowUpStatus::Pending, 403);

        $quoteFollowUp->update([
            'status' => QuoteFollowUpStatus::Cancelled->value,
            'cancelled_at' => now(),
        ]);

        return back()->with('toast', ['type' => 'success', 'message' => __('Follow-up cancelled successfully.')]);
    }

    public function sendFollowUpNow(Request $request, Quote $quote, QuoteFollowUp $quoteFollowUp): RedirectResponse
    {
        $workspace = $request->user()?->currentWorkspace;

        abort_unless($workspace instanceof Workspace && $quote->workspace_id === $workspace->id, 404);
        abort_unless($quoteFollowUp->quote_id === $quote->id, 404);
        abort_unless($quoteFollowUp->status === QuoteFollowUpStatus::Pending, 403);

        SendFollowUpJob::dispatch($quoteFollowUp->id);

        return back()->with('toast', ['type' => 'success', 'message' => __('Follow-up will be sent shortly.')]);
    }
}

<?php

namespace App\Http\Controllers;

use App\Enums\QuoteFollowUpStatus;
use App\Enums\QuoteStatus;
use App\Http\Requests\Quotes\StoreQuoteRequest;
use App\Http\Requests\Quotes\UpdateQuoteRequest;
use App\Http\Requests\Quotes\UpdateQuoteStatusRequest;
use App\Jobs\SendFollowUpJob;
use App\Models\Quote;
use App\Models\QuoteFollowUp;
use App\Models\QuoteTemplate;
use App\Models\User;
use App\Models\Workspace;
use App\Services\Quotes\QuoteAnalyticsService;
use App\Services\BuilderLookupService;
use App\Services\Quotes\QuoteService;
use App\Services\WorkspaceSettings\WorkspaceSettingsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class QuoteController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request, QuoteService $quoteService, WorkspaceSettingsService $workspaceSettingsService): Response | JsonResponse
    {
        $workspace = $request->user()?->currentWorkspace;

        abort_unless($workspace instanceof Workspace, 404);

        $filters = [
            'search' => $request->string('search')->toString(),
            'status' => $request->string('status')->toString(),
            'sort' => $request->string('sort')->toString() ?: 'newest',
        ];

        $quotes = $quoteService->paginateForIndex($workspace, $filters);

        if ($request->wantsJson()) {
            return response()->json($quotes);
        }

        return Inertia::render('quotes/Index', [
            'filters' => $filters,
            'quotes' => $quotes,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(Request $request, QuoteService $quoteService, WorkspaceSettingsService $workspaceSettingsService, BuilderLookupService $builderLookupService): Response
    {
        $workspace = $request->user()?->currentWorkspace;

        abort_unless($workspace instanceof Workspace, 404);

        $templateId = $request->integer('template_id');
        $template = null;

        if ($templateId > 0) {
            $template = QuoteTemplate::query()
                ->where('id', $templateId)
                ->where('workspace_id', $workspace->id)
                ->first();
        }

        $settings = $workspaceSettingsService->builderSettings($workspace);
        $validityDays = $settings['quotes']['validity_days'] ?? 30;
        $initialState = [
            'id' => null,
            'number' => null,
            'title' => '',
            'status' => 'draft',
            'client_id' => null,
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
            ...$builderLookupService->getFullLookups($workspace),
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

        $quote->load([
            'client',
            'assignee:id,name',
            'workspace',
            'sections.lineItems.taxes',
            'comments.user:id,name',
            'versions:id,version,number,created_at',
            'tasks.assignedTo:id,name',
            'tasks.assignedBy:id,name',
            'tasks.status',
        ]);

        $taskStatuses = \App\Models\TaskStatus::where('workspace_id', $workspace->id)
            ->orderBy('sort_order')
            ->get(['id', 'name', 'slug', 'color', 'sort_order']);

        $quote->setRelation('task_statuses', $taskStatuses);

        $quote->setRelation('versions', $quote->versions()->withoutGlobalScopes()->get(['id', 'version', 'number', 'created_at']));

        $quote->loadMissing([
            'template:id,name,layout',
            'creator:id,name,email',
            'quoteFollowUps.step:id,follow_up_sequence_id,channel,subject,message_template,day_offset',
            'winProbability.signals',
        ]);

        $quote = $this->transformQuote($quote);

        $teamMembers = User::whereHas('workspaces', function ($query) use ($workspace) {
            $query->where('workspace_id', $workspace->id);
        })->select('id', 'name', 'email')->get();

        return Inertia::render('quotes/Show', [
            'quote' => $quote,
            'settings' => $workspaceSettingsService->builderSettings($workspace),
            'quoteStatuses' => QuoteStatus::all(),
            'teamMembers' => $teamMembers,
        ]);
    }

    public function availableUsers(Request $request, Quote $quote): JsonResponse
    {
        $workspace = $request->user()?->currentWorkspace;

        abort_unless($workspace instanceof Workspace && $quote->workspace_id === $workspace->id, 404);

        $users = User::whereHas('workspaces', function ($query) use ($workspace) {
            $query->where('workspace_id', $workspace->id);
        })->select('id', 'name', 'email')->get();

        return response()->json($users);
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
            'quote' => $quote,
            'analytics' => $analyticsService->getAnalytics($quote),
            'quoteStatuses' => QuoteStatus::all(),
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Request $request, Quote $quote, QuoteService $quoteService, WorkspaceSettingsService $workspaceSettingsService, BuilderLookupService $builderLookupService): Response
    {
        $workspace = $request->user()?->currentWorkspace;

        abort_unless($workspace instanceof Workspace && $quote->workspace_id === $workspace->id, 404);

        return Inertia::render('quotes/Edit', [
            'initialState' => $quoteService->toBuilderPayload($quote),
            'settings' => $workspaceSettingsService->builderSettings($workspace),
            ...$builderLookupService->getFullLookups($workspace),
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

    public function restoreVersion(Request $request, Quote $quote, Quote $version, QuoteService $quoteService): RedirectResponse
    {
        $workspace = $request->user()?->currentWorkspace;

        abort_unless($workspace instanceof Workspace && $quote->workspace_id === $workspace->id, 404);
        abort_unless($version->parent_quote_id === $quote->id, 403, 'Invalid version.');

        $quoteService->restoreVersion($quote, $version);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Quote restored to version :version.', ['version' => $version->version])]);

        return to_route('quotes.show', $quote);
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

    public function handover(Request $request, Quote $quote, QuoteService $quoteService): RedirectResponse
    {
        $workspace = $request->user()?->currentWorkspace;

        abort_unless($workspace instanceof Workspace && $quote->workspace_id === $workspace->id, 404);

        $validated = $request->validate([
            'assigned_to' => 'required|integer|exists:users,id',
        ]);

        $quoteService->handover($quote, $validated['assigned_to']);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Quote ownership transferred successfully.')]);

        return back();
    }

    private function transformQuote(Quote $quote): Quote
    {
        $quote->subtotal = $quote->base_subtotal;
        $quote->discount_amount = $quote->base_discount_amount;
        $quote->tax_amount = $quote->base_tax_amount;
        $quote->total = $quote->base_total;
        $quote->currency = $quote->base_currency;

        foreach ($quote->sections as $section) {
            foreach ($section->lineItems as $lineItem) {
                $lineItem->unit_price = $lineItem->base_unit_price ?? $lineItem->unit_price;
                $lineItem->subtotal = $lineItem->base_subtotal ?? $lineItem->subtotal;
                $lineItem->tax_amount = $lineItem->base_tax_amount ?? $lineItem->tax_amount;
                $lineItem->total = $lineItem->base_total ?? $lineItem->total;

                foreach ($lineItem->taxes as $tax) {
                    $tax->tax_amount = $tax->base_tax_amount ?? $tax->tax_amount;
                }
            }
        }

        return $quote;
    }
}

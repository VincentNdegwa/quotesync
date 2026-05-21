<?php

namespace App\Http\Controllers;

use App\Enums\Feature;
use App\Exceptions\LimitExceededException;
use App\Http\Requests\QuoteTemplates\StoreQuoteTemplateRequest;
use App\Http\Requests\QuoteTemplates\UpdateQuoteTemplateRequest;
use App\Models\QuoteTemplate;
use App\Models\Workspace;
use App\Services\BuilderLookupService;
use App\Services\Quotes\QuoteTemplateService;
use App\Services\UsageLimitService;
use App\Services\WorkspaceSettings\WorkspaceSettingsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class QuoteTemplateController extends Controller
{
    public function __construct(
        private UsageLimitService $usageLimitService,
    ) {}

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request, QuoteTemplateService $quoteTemplateService): Response
    {
        $workspace = $request->user()?->currentWorkspace;

        abort_unless($workspace instanceof Workspace, 404);

        $filters = [
            'search' => $request->string('search')->toString(),
            'is_active' => $request->string('is_active')->toString(),
        ];

        return Inertia::render('configuration/templates/Index', [
            'filters' => $filters,
            'templates' => $quoteTemplateService->paginateForIndex($workspace, $filters),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(Request $request, WorkspaceSettingsService $workspaceSettingsService, BuilderLookupService $builderLookupService): Response
    {
        $workspace = $request->user()?->currentWorkspace;

        abort_unless($workspace instanceof Workspace, 404);

        $settings = $workspaceSettingsService->builderSettings($workspace);

        return Inertia::render('configuration/templates/Create', [
            'initialState' => [
                'id' => null,
                'description' => '',
                'industry' => '',
                'tax_amount' => 0,
                'total' => 0,
                'is_active' => true,
                'is_system' => false,
                'sections' => [
                    [
                        'id' => null,
                        'title' => 'Services',
                        'sort_order' => 0,
                        'line_items' => [],
                    ],
                ],
            ],
            'settings' => $settings,
            ...$builderLookupService->getTemplateLookups($workspace),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreQuoteTemplateRequest $request, QuoteTemplateService $quoteTemplateService): RedirectResponse
    {
        $workspace = $request->user()?->currentWorkspace;

        abort_unless($workspace instanceof Workspace, 404);

        if (!$this->usageLimitService->canPerformOperation($workspace, Feature::MAX_TEMPLATES)) {
            throw new LimitExceededException($this->usageLimitService->getLimitReachedMessage(Feature::MAX_TEMPLATES));
        }

        $payload = $request->validated();
        $payload['created_by'] = $request->user()?->id;

        $template = $quoteTemplateService->create($workspace, $payload);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Template saved.')]);

        return to_route('quote-templates.edit', $template);
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, QuoteTemplate $quoteTemplate): Response
    {
        $workspace = $request->user()?->currentWorkspace;

        abort_unless($workspace instanceof Workspace && $quoteTemplate->workspace_id === $workspace->id, 404);

        return Inertia::render('configuration/templates/Show', [
            'template' => $quoteTemplate,
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Request $request, QuoteTemplate $quoteTemplate, WorkspaceSettingsService $workspaceSettingsService, BuilderLookupService $builderLookupService, QuoteTemplateService $quoteTemplateService): Response
    {
        $workspace = $request->user()?->currentWorkspace;

        abort_unless($workspace instanceof Workspace && $quoteTemplate->workspace_id === $workspace->id, 404);

        return Inertia::render('configuration/templates/Edit', [
            'templateId' => $quoteTemplate->id,
            'initialState' => $quoteTemplateService->toBuilderPayload($quoteTemplate),
            'settings' => $workspaceSettingsService->builderSettings($workspace),
            ...$builderLookupService->getTemplateLookups($workspace),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateQuoteTemplateRequest $request, QuoteTemplate $quoteTemplate, QuoteTemplateService $quoteTemplateService): RedirectResponse
    {
        $workspace = $request->user()?->currentWorkspace;

        abort_unless($workspace instanceof Workspace && $quoteTemplate->workspace_id === $workspace->id, 404);

        $quoteTemplateService->update($quoteTemplate, $request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Template updated.')]);

        return back();
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, QuoteTemplate $quoteTemplate, QuoteTemplateService $quoteTemplateService): RedirectResponse
    {
        $workspace = $request->user()?->currentWorkspace;

        abort_unless($workspace instanceof Workspace && $quoteTemplate->workspace_id === $workspace->id, 404);

        $quoteTemplateService->delete($quoteTemplate);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Template deleted.')]);

        return to_route('quote-templates.index');
    }

    /**
     * Get template layout.
     */
    public function getLayout(Request $request, QuoteTemplate $quoteTemplate): JsonResponse
    {
        $workspace = $request->user()?->currentWorkspace;

        abort_unless($workspace instanceof Workspace && $quoteTemplate->workspace_id === $workspace->id, 404);

        $sections = $quoteTemplate->sections()
            ->with(['lineItems' => fn ($q) => $q->orderBy('sort_order'), 'lineItems.taxes'])
            ->get();

        return response()->json([
            'layout' => $quoteTemplate->layout,
            'sections' => $sections,
        ]);
    }
}

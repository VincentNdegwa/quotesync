<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreQuoteTemplateRequest;
use App\Http\Requests\UpdateQuoteTemplateRequest;
use App\Models\CatalogItem;
use App\Models\QuoteTemplate;
use App\Models\Tax;
use App\Models\Workspace;
use App\Services\Quotes\QuoteTemplateService;
use App\Services\WorkspaceSettings\WorkspaceSettingsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class QuoteTemplateController extends Controller
{
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
            'templates' => $quoteTemplateService->paginateForIndex($workspace, $filters)
                ->through(fn (QuoteTemplate $template): array => [
                    'id' => $template->id,
                    'name' => $template->name,
                    'description' => $template->description,
                    'industry' => $template->industry,
                    'is_active' => (bool) $template->is_active,
                    'is_system' => (bool) $template->is_system,
                    'usage_count' => $template->usage_count,
                    'sections_count' => $template->sections_count,
                    'updated_at' => $template->updated_at?->toISOString(),
                ]),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(Request $request, WorkspaceSettingsService $workspaceSettingsService): Response
    {
        $workspace = $request->user()?->currentWorkspace;

        abort_unless($workspace instanceof Workspace, 404);

        $settings = $workspaceSettingsService->builderSettings($workspace);

        return Inertia::render('configuration/templates/Create', [
            'initialState' => [
                'id' => null,
                'number' => null,
                'title' => '',
                'status' => 'draft',
                'client_id' => null,
                'assigned_to' => null,
                'currency' => $settings['workspace']['currency'],
                'valid_until' => null,
                'description' => null,
                'industry' => null,
                'cover_message' => $settings['quotes']['default_cover_message'],
                'terms' => $settings['quotes']['default_terms'],
                'notes' => $settings['quotes']['default_notes'],
                'template_id' => null,
                'requires_deposit' => $settings['quotes']['require_deposit'],
                'deposit_amount' => null,
                'subtotal' => 0,
                'discount_amount' => 0,
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
            ...$this->builderLookups($workspace, $workspaceSettingsService),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreQuoteTemplateRequest $request, QuoteTemplateService $quoteTemplateService): RedirectResponse
    {
        $workspace = $request->user()?->currentWorkspace;

        abort_unless($workspace instanceof Workspace, 404);

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
            'template' => [
                'id' => $quoteTemplate->id,
                'name' => $quoteTemplate->name,
                'description' => $quoteTemplate->description,
                'industry' => $quoteTemplate->industry,
                'is_active' => (bool) $quoteTemplate->is_active,
                'is_system' => (bool) $quoteTemplate->is_system,
                'usage_count' => $quoteTemplate->usage_count,
                'updated_at' => $quoteTemplate->updated_at?->toISOString(),
            ],
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(
        Request $request,
        QuoteTemplate $quoteTemplate,
        QuoteTemplateService $quoteTemplateService,
        WorkspaceSettingsService $workspaceSettingsService,
    ): Response {
        $workspace = $request->user()?->currentWorkspace;

        abort_unless($workspace instanceof Workspace && $quoteTemplate->workspace_id === $workspace->id, 404);

        return Inertia::render('configuration/templates/Edit', [
            'templateId' => $quoteTemplate->id,
            'initialState' => $quoteTemplateService->toBuilderPayload($quoteTemplate),
            'settings' => $workspaceSettingsService->builderSettings($workspace),
            ...$this->builderLookups($workspace, $workspaceSettingsService),
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
            ->get()
            ->map(fn ($section) => [
                'id' => $section->id,
                'title' => $section->title,
                'sort_order' => $section->sort_order,
                'line_items' => $section->lineItems->map(fn ($item) => [
                    'id' => null,
                    'catalog_item_id' => $item->catalog_item_id,
                    'name' => $item->name,
                    'description' => $item->description,
                    'quantity' => (float) $item->quantity,
                    'unit' => $item->unit,
                    'unit_price' => (float) $item->unit_price,
                    'discount_percent' => (float) $item->discount_percent,
                    'is_optional' => (bool) $item->is_optional,
                    'notes' => $item->notes,
                    'sort_order' => $item->sort_order,
                    'subtotal' => 0,
                    'tax_amount' => 0,
                    'total' => 0,
                    'taxes' => $item->taxes->map(fn ($tax) => [
                        'tax_id' => $tax->tax_id,
                        'tax_label' => $tax->tax_label,
                        'tax_rate' => (float) $tax->tax_rate,
                    ])->values()->all(),
                ])->values()->all(),
            ])
            ->values()
            ->all();

        return response()->json([
            'layout' => $quoteTemplate->layout,
            'sections' => $sections,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function builderLookups(Workspace $workspace, WorkspaceSettingsService $workspaceSettingsService): array
    {
        $branding = $this->brandingPayload($workspace, $workspaceSettingsService);

        return [
            'branding' => $branding,
            'catalogItems' => CatalogItem::query()
                ->where('workspace_id', $workspace->id)
                ->where('is_active', true)
                ->with('taxes')
                ->orderByRaw('LOWER(name)')
                ->limit(300)
                ->get(),
            'taxes' => Tax::query()
                ->where('workspace_id', $workspace->id)
                ->where('is_active', true)
                ->orderByDesc('is_default')
                ->orderByRaw('LOWER(name)')
                ->get(),
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
}

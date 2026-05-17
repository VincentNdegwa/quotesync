<?php

namespace App\Http\Controllers;

use App\Enums\Feature;
use App\Exceptions\LimitExceededException;
use App\Http\Requests\Catalog\CatalogImportPreviewRequest;
use App\Http\Requests\Catalog\CatalogImportStoreRequest;
use App\Jobs\ImportCatalogItemsJob;
use App\Models\CatalogItem;
use App\Models\ConfigurationUnit;
use App\Models\ImportHistory;
use App\Models\Workspace;
use App\Services\Import\CatalogImportValidator;
use App\Services\UsageLimitService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CatalogImportController extends Controller
{
    public function create(): Response
    {
        $workspace = request()->user()?->currentWorkspace;

        abort_unless($workspace instanceof Workspace, 404);

        return Inertia::render('catalog/Import', [
            'units' => ConfigurationUnit::query()
                ->where('workspace_id', $workspace->id)
                ->where('is_active', true)
                ->orderByRaw('LOWER(name)')
                ->get(['id', 'name', 'symbol', 'is_active', 'created_at']),
            'skippedItems' => session()->get('skippedItems', []),
        ]);
    }

    public function template(): StreamedResponse
    {
        $headers = ['name', 'sku', 'unit_price', 'cost_price'];
        $rows = [
            ['Web Design Package', 'WEB-001', 100.00, 50.00],
            ['Logo Design', 'LOGO-001', 500.00, 100.00],
        ];

        return response()->streamDownload(function () use ($headers, $rows) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $headers);
            foreach ($rows as $row) {
                fputcsv($file, $row);
            }
            fclose($file);
        }, 'catalog-template.csv');
    }

    public function preview(CatalogImportPreviewRequest $request): JsonResponse
    {
        $workspace = $request->user()?->currentWorkspace;

        abort_unless($workspace instanceof Workspace, 404);

        $validated = $request->validated();

        $defaultUnit = ConfigurationUnit::query()
            ->where('workspace_id', $workspace->id)
            ->where('is_active', true)
            ->orderByRaw('LOWER(name)')
            ->value('name') ?? 'unit';

        $rows = array_map('str_getcsv', file($validated['file']->getRealPath()));
        $header = collect(array_shift($rows) ?? [])->map(fn ($value) => Str::lower(trim((string) $value)))->values()->all();

        $mapped = collect($rows)
            ->map(function (array $row) use ($header, $defaultUnit): array {
                $data = array_combine($header, $row) ?: [];

                return [
                    'name' => trim((string) ($data['name'] ?? '')),
                    'sku' => trim((string) ($data['sku'] ?? '')),
                    'unit' => $defaultUnit,
                    'unit_price' => (float) ($data['unit_price'] ?? 0),
                    'cost_price' => (float) ($data['cost_price'] ?? 0),
                ];
            })
            ->filter(fn (array $row): bool => $row['name'] !== '')
            ->values();

        $validator = new CatalogImportValidator;
        $validatedRows = $mapped->map(function ($row, $index) use ($validator) {
            return $validator->validate($row, $index + 2);
        });

        $errorCount = $validatedRows->where('valid', false)->count();

        $token = Str::uuid()->toString();

        Cache::put("catalog_import:{$workspace->id}:{$token}", $mapped->all(), now()->addMinutes(30));

        return response()->json([
            'detectedColumns' => $header,
            'requiredColumns' => ['name', 'sku', 'unit_price', 'cost_price'],
            'optionalColumns' => [],
            'previewRows' => $validatedRows->take(20)->all(),
            'importToken' => $token,
            'totalRows' => $validatedRows->count(),
            'errorCount' => $errorCount,
        ]);
    }

    public function store(CatalogImportStoreRequest $request): RedirectResponse
    {
        $workspace = $request->user()?->currentWorkspace;

        abort_unless($workspace instanceof Workspace, 404);

        $validated = $request->validated();
        $token = $validated['import_token'];
        $columnMapping = $validated['column_mapping'] ?? [];
        $unitMappingMode = $validated['unit_mapping_mode'];
        $unitForAll = $validated['unit_for_all'] ?? '';
        $unitMapping = $validated['unit_mapping'] ?? [];
        $rows = collect(Cache::pull("catalog_import:{$workspace->id}:{$token}", []));

        if ($rows->isEmpty()) {
            Inertia::flash('toast', ['type' => 'error', 'message' => 'Import token expired. Please upload again.']);

            return back();
        }

        if ($columnMapping) {
            $rows = $rows->map(function ($row) use ($columnMapping) {
                $mapped = [];
                foreach ($columnMapping as $targetField => $sourceColumn) {
                    if ($sourceColumn && $sourceColumn !== '__skip__' && isset($row[$sourceColumn])) {
                        $mapped[$targetField] = $row[$sourceColumn];
                    }
                }

                return $mapped;
            });
        }

        $workspace->loadCount('catalogItems');
        $usageLimitService = app(UsageLimitService::class);
        $limit = $usageLimitService->getLimit($workspace, Feature::MAX_CATALOG_ITEMS);
        $currentUsage = $usageLimitService->getCurrentUsage($workspace, Feature::MAX_CATALOG_ITEMS);

        $canImport = $limit !== null ? ($limit - $currentUsage) : null;

        if ($canImport !== null && $canImport <= 0) {
            throw new LimitExceededException('You have reached your Catalog Items limit. Please upgrade your plan to import more items.');
        }

        if ($canImport !== null && $rows->count() > $canImport) {
            $skippedDueToLimit = $rows->count() - $canImport;
            $rows = $rows->take($canImport);
        } else {
            $skippedDueToLimit = 0;
        }

        if ($rows->count() > 100) {
            $importHistory = ImportHistory::create([
                'workspace_id' => $workspace->id,
                'user_id' => $request->user()?->id,
                'type' => 'catalog',
                'status' => 'pending',
                'total_rows' => $rows->count(),
                'skipped_due_to_limit' => $skippedDueToLimit,
            ]);

            ImportCatalogItemsJob::dispatch(
                $workspace->id,
                $request->user()?->id,
                $rows->all(),
                $importHistory->id,
                $unitMappingMode,
                $unitForAll,
                $unitMapping
            );

            $message = $skippedDueToLimit > 0 
                ? "Catalog import queued. {$skippedDueToLimit} items skipped due to limit."
                : 'Catalog import queued.';

            Inertia::flash('toast', ['type' => $skippedDueToLimit > 0 ? 'warning' : 'success', 'message' => $message]);

            return to_route('catalog.index');
        }

        $defaultUnit = ConfigurationUnit::query()
            ->where('workspace_id', $workspace->id)
            ->where('is_active', true)
            ->orderByRaw('LOWER(name)')
            ->value('name') ?? 'unit';

        $existingSkus = CatalogItem::query()
            ->where('workspace_id', $workspace->id)
            ->whereIn('sku', $rows->pluck('sku')->filter()->all())
            ->pluck('sku')
            ->all();

        $imported = 0;
        $skipped = 0;
        $skippedItems = [];

        foreach ($rows as $index => $row) {
            $sku = trim((string) ($row['sku'] ?? ''));

            if ($sku !== '' && in_array($sku, $existingSkus, true)) {
                $skipped++;
                $skippedItems[] = [
                    'line' => $index + 2,
                    'name' => $row['name'],
                    'sku' => $sku,
                    'reason' => 'Duplicate SKU',
                ];

                continue;
            }

            $unit = $defaultUnit;
            if ($unitMappingMode === 'all' && $unitForAll !== '') {
                $unit = $unitForAll;
            } elseif ($unitMappingMode === 'individual' && isset($unitMapping[$index + 2])) {
                $unit = $unitMapping[$index + 2];
            }

            CatalogItem::query()->create([
                'workspace_id' => $workspace->id,
                'created_by' => $request->user()?->id,
                'name' => $row['name'],
                'sku' => $sku !== '' ? $sku : null,
                'unit' => $unit,
                'unit_price' => $row['unit_price'],
                'cost_price' => $row['cost_price'],
                'is_active' => true,
            ]);

            $imported++;
        }

        $totalSkipped = $skipped + $skippedDueToLimit;
        $message = $totalSkipped > 0 
            ? "Import complete. {$imported} imported, {$totalSkipped} skipped (including {$skippedDueToLimit} due to limit)."
            : "Import complete. {$imported} imported.";

        Inertia::flash('toast', [
            'type' => $totalSkipped > 0 ? 'warning' : 'success',
            'message' => $message,
        ]);

        if ($totalSkipped > 0) {
            session()->put('skippedItems', $skippedItems);
        } else {
            session()->forget('skippedItems');
        }

        return back();
    }
}

<?php

namespace App\Http\Controllers;

use App\Jobs\ImportCatalogItemsJob;
use App\Models\CatalogItem;
use App\Models\ImportHistory;
use App\Models\Workspace;
use App\Services\Import\CatalogImportValidator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CatalogImportController extends Controller
{
    public function create(): Response
    {
        return Inertia::render('catalog/Import');
    }

    public function template(): StreamedResponse
    {
        $headers = ['name', 'sku', 'unit', 'unit_price', 'cost_price'];
        $rows = [
            ['Web Design Package', 'WEB-001', 'hr', 100.00, 50.00],
            ['Logo Design', 'LOGO-001', 'unit', 500.00, 100.00],
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

    public function preview(Request $request): JsonResponse
    {
        $workspace = $request->user()?->currentWorkspace;

        abort_unless($workspace instanceof Workspace, 404);

        $validated = $request->validate([
            'file' => ['required', 'file', 'mimes:csv,txt', 'max:5120'],
        ]);

        $rows = array_map('str_getcsv', file($validated['file']->getRealPath()));
        $header = collect(array_shift($rows) ?? [])->map(fn ($value) => Str::lower(trim((string) $value)))->values()->all();

        $mapped = collect($rows)
            ->map(function (array $row) use ($header): array {
                $data = array_combine($header, $row) ?: [];

                return [
                    'name' => trim((string) ($data['name'] ?? '')),
                    'sku' => trim((string) ($data['sku'] ?? '')),
                    'unit' => trim((string) ($data['unit'] ?? 'unit')),
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

        $detectedColumns = $header;
        $requiredColumns = ['name', 'sku', 'unit', 'unit_price', 'cost_price'];
        $optionalColumns = [];

        $token = Str::uuid()->toString();

        Cache::put("catalog_import:{$workspace->id}:{$token}", $mapped->all(), now()->addMinutes(30));

        return response()->json([
            'detectedColumns' => $detectedColumns,
            'requiredColumns' => $requiredColumns,
            'optionalColumns' => $optionalColumns,
            'previewRows' => $validatedRows->take(20)->all(),
            'importToken' => $token,
            'totalRows' => $validatedRows->count(),
            'errorCount' => $errorCount,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $workspace = $request->user()?->currentWorkspace;

        abort_unless($workspace instanceof Workspace, 404);

        $validated = $request->validate([
            'import_token' => ['required', 'string'],
            'column_mapping' => ['array'],
        ]);

        $token = $validated['import_token'];
        $columnMapping = $validated['column_mapping'] ?? [];
        $rows = collect(Cache::pull("catalog_import:{$workspace->id}:{$token}", []));

        if ($rows->isEmpty()) {
            Inertia::flash('toast', ['type' => 'error', 'message' => __('Import token expired. Please upload again.')]);

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

        if ($rows->count() > 100) {
            $importHistory = ImportHistory::create([
                'workspace_id' => $workspace->id,
                'user_id' => $request->user()?->id,
                'type' => 'catalog',
                'status' => 'pending',
                'total_rows' => $rows->count(),
            ]);

            ImportCatalogItemsJob::dispatch($workspace->id, $request->user()?->id, $rows->all(), $importHistory->id);

            Inertia::flash('toast', ['type' => 'success', 'message' => __('Catalog import queued.')]);

            return to_route('catalog.index');
        }

        $existingSkus = CatalogItem::query()
            ->where('workspace_id', $workspace->id)
            ->whereIn('sku', $rows->pluck('sku')->filter()->all())
            ->pluck('sku')
            ->all();

        $imported = 0;
        $skipped = 0;

        foreach ($rows as $row) {
            $sku = trim((string) ($row['sku'] ?? ''));

            if ($sku !== '' && in_array($sku, $existingSkus, true)) {
                $skipped++;

                continue;
            }

            CatalogItem::query()->create([
                'workspace_id' => $workspace->id,
                'created_by' => $request->user()?->id,
                'name' => $row['name'],
                'sku' => $sku !== '' ? $sku : null,
                'unit' => in_array($row['unit'], ['hr', 'day', 'unit', 'sqm', 'kg', 'm', 'lot', 'month'], true) ? $row['unit'] : 'unit',
                'unit_price' => $row['unit_price'],
                'cost_price' => $row['cost_price'],
                'is_active' => true,
            ]);

            $imported++;
        }

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Import complete. :imported imported, :skipped skipped.', [
                'imported' => $imported,
                'skipped' => $skipped,
            ]),
        ]);

        return to_route('catalog.index');
    }
}

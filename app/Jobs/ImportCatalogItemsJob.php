<?php

namespace App\Jobs;

use App\Models\CatalogItem;
use App\Models\ImportHistory;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ImportCatalogItemsJob implements ShouldQueue
{
    use Queueable;

    /**
     * @param  array<int, array<string, mixed>>  $rows
     */
    public function __construct(
        public int $workspaceId,
        public ?int $createdBy,
        public array $rows,
        public ?int $importHistoryId = null,
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $importHistory = $this->importHistoryId ? ImportHistory::find($this->importHistoryId) : null;

        if ($importHistory) {
            $importHistory->update([
                'status' => 'processing',
                'started_at' => now(),
                'total_rows' => count($this->rows),
            ]);
        }

        $allowedUnits = ['hr', 'day', 'unit', 'sqm', 'kg', 'm', 'lot', 'month'];

        $existingSkus = CatalogItem::query()
            ->withoutGlobalScopes()
            ->where('workspace_id', $this->workspaceId)
            ->whereIn('sku', collect($this->rows)->pluck('sku')->filter()->all())
            ->pluck('sku')
            ->map(fn (string $sku): string => Str::lower($sku))
            ->all();

        $imported = 0;
        $skipped = 0;
        $errors = [];

        foreach ($this->rows as $index => $row) {
            $name = trim((string) ($row['name'] ?? ''));

            if ($name === '') {
                $skipped++;
                $errors[] = "Row {$index}: Missing name";

                continue;
            }

            $sku = Str::lower(trim((string) ($row['sku'] ?? '')));

            if ($sku !== '' && in_array($sku, $existingSkus, true)) {
                $skipped++;
                $errors[] = "Row {$index}: Duplicate SKU {$sku}";

                continue;
            }

            $unit = trim((string) ($row['unit'] ?? 'unit'));

            try {
                CatalogItem::query()
                    ->withoutGlobalScopes()
                    ->create([
                        'workspace_id' => $this->workspaceId,
                        'created_by' => $this->createdBy,
                        'name' => $name,
                        'sku' => $sku !== '' ? $sku : null,
                        'unit' => in_array($unit, $allowedUnits, true) ? $unit : 'unit',
                        'unit_price' => (float) ($row['unit_price'] ?? 0),
                        'cost_price' => (float) ($row['cost_price'] ?? 0),
                        'is_active' => true,
                    ]);

                $imported++;
            } catch (\Exception $e) {
                $skipped++;
                $errors[] = "Row {$index}: {$e->getMessage()}";
            }

            if ($importHistory && $index % 10 === 0) {
                $importHistory->update([
                    'processed_rows' => $index + 1,
                    'failed_rows' => $skipped,
                ]);
            }
        }

        if ($importHistory) {
            $importHistory->update([
                'status' => 'completed',
                'processed_rows' => count($this->rows),
                'failed_rows' => $skipped,
                'error_details' => $errors,
                'completed_at' => now(),
            ]);
        }

        Log::info('Catalog import complete', [
            'workspace_id' => $this->workspaceId,
            'imported' => $imported,
            'skipped' => $skipped,
        ]);
    }
}

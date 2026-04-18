<?php

namespace App\Services\Taxes;

use App\Models\Tax;
use App\Models\Workspace;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class TaxService
{
    /**
     * @return Collection<int, Tax>
     */
    public function allForWorkspace(Workspace $workspace): Collection
    {
        return Tax::query()
            ->where('workspace_id', $workspace->id)
            ->orderByDesc('is_default')
            ->orderByRaw('LOWER(name)')
            ->get();
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function create(Workspace $workspace, array $payload): Tax
    {
        return DB::transaction(function () use ($workspace, $payload): Tax {
            if (($payload['is_default'] ?? false) === true) {
                Tax::query()
                    ->where('workspace_id', $workspace->id)
                    ->update(['is_default' => false]);
            }

            return Tax::query()->create([
                ...$payload,
                'workspace_id' => $workspace->id,
            ]);
        });
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function update(Tax $tax, array $payload): Tax
    {
        return DB::transaction(function () use ($tax, $payload): Tax {
            if (($payload['is_default'] ?? false) === true) {
                Tax::query()
                    ->where('workspace_id', $tax->workspace_id)
                    ->where('id', '!=', $tax->id)
                    ->update(['is_default' => false]);
            }

            $tax->fill($payload)->save();

            return $tax->refresh();
        });
    }

    public function delete(Tax $tax): void
    {
        $tax->delete();
    }
}

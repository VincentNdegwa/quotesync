<?php

namespace App\Models;

use Database\Factories\CatalogItemVariantFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;

#[Fillable([
    'catalog_item_id',
    'name',
    'sku',
    'unit_price',
    'cost_price',
    'is_default',
    'sort_order',
])]
class CatalogItemVariant extends Model
{
    /** @use HasFactory<CatalogItemVariantFactory> */
    use HasFactory;

    protected static function booted(): void
    {
        static::addGlobalScope('workspace', function (Builder $query): void {
            $workspaceId = Auth::user()?->current_workspace_id;

            if ($workspaceId !== null) {
                $query->whereHas('catalogItem', function (Builder $q) use ($workspaceId) {
                    $q->where('workspace_id', $workspaceId);
                });
            }
        });

        static::saving(function (CatalogItemVariant $variant) {
            if ($variant->is_default) {
                CatalogItemVariant::where('catalog_item_id', $variant->catalog_item_id)
                    ->where('id', '!=', $variant->id)
                    ->update(['is_default' => false]);
            }
        });
    }

    public function catalogItem(): BelongsTo
    {
        return $this->belongsTo(CatalogItem::class);
    }

    protected function casts(): array
    {
        return [
            'is_default' => 'boolean',
            'unit_price' => 'decimal:2',
            'cost_price' => 'decimal:2',
        ];
    }
}

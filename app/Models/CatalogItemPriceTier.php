<?php

namespace App\Models;

use App\Enums\PricingType;
use Database\Factories\CatalogItemPriceTierFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;

#[Fillable([
    'catalog_item_id',
    'variant_id',
    'min_quantity',
    'max_quantity',
    'pricing_type',
    'unit_price',
    'discount_percent',
])]
class CatalogItemPriceTier extends Model
{
    /** @use HasFactory<CatalogItemPriceTierFactory> */
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
    }

    public function catalogItem(): BelongsTo
    {
        return $this->belongsTo(CatalogItem::class);
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(CatalogItemVariant::class);
    }

    protected function casts(): array
    {
        return [
            'pricing_type' => PricingType::class,
            'unit_price' => 'decimal:2',
            'discount_percent' => 'decimal:2',
        ];
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class PriceTier extends Model
{
    protected $fillable = [
        'catalog_price_tier_id',
        'priceable_id',
        'priceable_type',
        'variant_id',
        'min_quantity',
        'max_quantity',
        'pricing_type',
        'value',
    ];

    protected $casts = [
        'min_quantity' => 'integer',
        'max_quantity' => 'integer',
        'value' => 'decimal:2',
    ];

    public function priceable(): MorphTo
    {
        return $this->morphTo();
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(CatalogItemVariant::class);
    }
}

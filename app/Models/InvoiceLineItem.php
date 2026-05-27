<?php

namespace App\Models;

use App\Enums\DiscountType;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'invoice_id',
    'invoice_section_id',
    'catalog_item_id',
    'catalog_item_variant_id',
    'name',
    'description',
    'quantity',
    'unit',
    'unit_price',
    'base_unit_price',
    'tax_rate',
    'discount_type',
    'discount_value',
    'subtotal',
    'base_subtotal',
    'tax_amount',
    'base_tax_amount',
    'total',
    'base_total',
    'sort_order',
])]
class InvoiceLineItem extends Model
{
    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function section(): BelongsTo
    {
        return $this->belongsTo(InvoiceSection::class, 'invoice_section_id');
    }

    public function catalogItem(): BelongsTo
    {
        return $this->belongsTo(CatalogItem::class);
    }

    public function catalogItemVariant(): BelongsTo
    {
        return $this->belongsTo(CatalogItemVariant::class);
    }

    public function taxes(): HasMany
    {
        return $this->hasMany(InvoiceLineItemTax::class)->orderBy('id');
    }

    /**
     * @return HasMany<PriceTier, $this>
     */
    public function priceTiers(): HasMany
    {
        return $this->hasMany(PriceTier::class, 'priceable_id')->where('priceable_type', 'invoice_line_item');
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:2',
            'unit_price' => 'decimal:2',
            'base_unit_price' => 'decimal:2',
            'tax_rate' => 'decimal:2',
            'discount_type' => DiscountType::class,
            'discount_value' => 'decimal:2',
            'subtotal' => 'decimal:2',
            'base_subtotal' => 'decimal:2',
            'tax_amount' => 'decimal:2',
            'base_tax_amount' => 'decimal:2',
            'total' => 'decimal:2',
            'base_total' => 'decimal:2',
        ];
    }
}

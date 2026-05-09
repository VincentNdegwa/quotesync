<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'invoice_id',
    'catalog_item_id',
    'name',
    'description',
    'quantity',
    'unit',
    'unit_price',
    'base_unit_price',
    'tax_rate',
    'discount_percent',
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

    public function catalogItem(): BelongsTo
    {
        return $this->belongsTo(CatalogItem::class);
    }

    public function taxes(): HasMany
    {
        return $this->hasMany(InvoiceLineItemTax::class)->orderBy('id');
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
            'discount_percent' => 'decimal:2',
            'subtotal' => 'decimal:2',
            'base_subtotal' => 'decimal:2',
            'tax_amount' => 'decimal:2',
            'base_tax_amount' => 'decimal:2',
            'total' => 'decimal:2',
            'base_total' => 'decimal:2',
        ];
    }
}

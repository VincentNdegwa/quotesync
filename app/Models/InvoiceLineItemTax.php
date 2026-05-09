<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'invoice_line_item_id',
    'tax_id',
    'tax_label',
    'tax_rate',
    'inclusive',
    'tax_amount',
    'base_tax_amount',
])]
class InvoiceLineItemTax extends Model
{
    /**
     * @return BelongsTo<InvoiceLineItem, $this>
     */
    public function lineItem(): BelongsTo
    {
        return $this->belongsTo(InvoiceLineItem::class, 'invoice_line_item_id');
    }

    /**
     * @return BelongsTo<Tax, $this>
     */
    public function tax(): BelongsTo
    {
        return $this->belongsTo(Tax::class);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'tax_rate' => 'decimal:3',
            'inclusive' => 'boolean',
            'tax_amount' => 'decimal:2',
            'base_tax_amount' => 'decimal:2',
        ];
    }
}

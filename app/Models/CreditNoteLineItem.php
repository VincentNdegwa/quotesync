<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'credit_note_id',
    'name',
    'description',
    'quantity',
    'unit',
    'unit_price',
    'base_unit_price',
    'tax_amount',
    'base_tax_amount',
    'subtotal',
    'base_subtotal',
    'total',
    'base_total',
])]
class CreditNoteLineItem extends Model
{
    /**
     * @return BelongsTo<CreditNote, $this>
     */
    public function creditNote(): BelongsTo
    {
        return $this->belongsTo(CreditNote::class);
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
            'tax_amount' => 'decimal:2',
            'base_tax_amount' => 'decimal:2',
            'subtotal' => 'decimal:2',
            'base_subtotal' => 'decimal:2',
            'total' => 'decimal:2',
            'base_total' => 'decimal:2',
        ];
    }
}

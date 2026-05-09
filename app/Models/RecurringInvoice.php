<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'workspace_id',
    'client_id',
    'created_by',
    'name',
    'currency',
    'subtotal',
    'tax_amount',
    'discount_amount',
    'total',
    'layout_snapshot',
    'sections',
    'frequency',
    'interval',
    'start_date',
    'end_date',
    'next_invoice_date',
    'is_active',
])]
class RecurringInvoice extends Model
{
    protected function casts(): array
    {
        return [
            'subtotal' => 'decimal:2',
            'tax_amount' => 'decimal:2',
            'discount_amount' => 'decimal:2',
            'total' => 'decimal:2',
            'layout_snapshot' => 'array',
            'sections' => 'array',
            'start_date' => 'date',
            'end_date' => 'date',
            'next_invoice_date' => 'date',
            'is_active' => 'boolean',
        ];
    }

    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }
}

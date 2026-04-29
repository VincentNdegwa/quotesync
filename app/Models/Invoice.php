<?php

namespace App\Models;

use App\Enums\InvoiceStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'workspace_id',
    'client_id',
    'quote_id',
    'invoice_number',
    'title',
    'cover_message',
    'terms',
    'notes',
    'layout_snapshot',
    'currency',
    'subtotal',
    'tax_amount',
    'discount_amount',
    'total',
    'paid_amount',
    'balance_due',
    'status',
    'issue_date',
    'due_date',
    'paid_date',
    'sent_at',
    'created_by',
    'base_currency',
    'fx_rate',
    'base_total',
])]
class Invoice extends Model
{
    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function quote(): BelongsTo
    {
        return $this->belongsTo(Quote::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function lineItems(): HasMany
    {
        return $this->hasMany(InvoiceLineItem::class)->orderBy('sort_order');
    }

    public function activities(): HasMany
    {
        return $this->hasMany(InvoiceActivity::class)->orderBy('created_at', 'desc');
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => InvoiceStatus::class,
            'layout_snapshot' => 'array',
            'subtotal' => 'decimal:2',
            'tax_amount' => 'decimal:2',
            'discount_amount' => 'decimal:2',
            'total' => 'decimal:2',
            'paid_amount' => 'decimal:2',
            'balance_due' => 'decimal:2',
            'fx_rate' => 'decimal:6',
            'base_total' => 'decimal:2',
            'issue_date' => 'date',
            'due_date' => 'date',
            'paid_date' => 'date',
            'sent_at' => 'datetime',
        ];
    }
}

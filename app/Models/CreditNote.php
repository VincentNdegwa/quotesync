<?php

namespace App\Models;

use App\Enums\CreditNoteStatus;
use App\Enums\CreditNoteType;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'workspace_id',
    'invoice_id',
    'client_id',
    'created_by',
    'credit_note_number',
    'title',
    'type',
    'reason',
    'currency',
    'amount',
    'tax_amount',
    'total',
    'issue_date',
    'due_date',
    'status',
    'pdf_url',
    'applied_at',
    'fx_rate',
    'base_amount',
    'base_total',
])]
class CreditNote extends Model
{
    protected function casts(): array
    {
        return [
            'type' => CreditNoteType::class,
            'status' => CreditNoteStatus::class,
            'amount' => 'decimal:2',
            'tax_amount' => 'decimal:2',
            'total' => 'decimal:2',
            'issue_date' => 'date',
            'due_date' => 'date',
            'applied_at' => 'datetime',
            'fx_rate' => 'decimal:15,6',
            'base_amount' => 'decimal:2',
            'base_total' => 'decimal:2',
        ];
    }

    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function lineItems(): HasMany
    {
        return $this->hasMany(CreditNoteLineItem::class);
    }
}

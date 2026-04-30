<?php

namespace App\Models;

use App\Enums\QuoteStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

#[Fillable([
    'workspace_id',
    'quote_uuid',
    'number',
    'title',
    'status',
    'approval_granted',
    'approval_granted_at',
    'client_id',
    'assigned_to',
    'currency',
    'base_currency',
    'fx_rate',
    'base_total',
    'cover_message',
    'notes',
    'terms',
    'valid_until',
    'version',
    'pdf_path',
    'template_id',
    'layout_snapshot',
    'parent_quote_id',
    'subtotal',
    'discount_amount',
    'tax_amount',
    'total',
    'requires_deposit',
    'deposit_amount',
    'sent_at',
    'viewed_at',
    'accepted_at',
    'declined_at',
    'decline_reason',
    'created_by',
    'signature_path',
    'signer_name',
    'signer_ip',
    'win_probability',
    'won_at',
    'lost_at',
])]
class Quote extends Model
{
    use SoftDeletes;

    protected static function booted(): void
    {
        static::addGlobalScope('workspace', function (Builder $query): void {
            $workspaceId = Auth::user()?->current_workspace_id;

            if ($workspaceId !== null) {
                $query->where('workspace_id', $workspaceId);
            }
        });

        static::creating(function (self $quote): void {
            if (! is_string($quote->quote_uuid) || trim($quote->quote_uuid) === '') {
                $quote->quote_uuid = (string) Str::uuid();
            }
        });
    }

    /**
     * @return BelongsTo<Workspace, $this>
     */
    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    /**
     * @return BelongsTo<Client, $this>
     */
    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * @return BelongsTo<QuoteTemplate, $this>
     */
    public function template(): BelongsTo
    {
        return $this->belongsTo(QuoteTemplate::class, 'template_id');
    }

    /**
     * @return BelongsTo<Quote, $this>
     */
    public function parentQuote(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_quote_id');
    }

    /**
     * @return HasMany<QuoteSection, $this>
     */
    public function sections(): HasMany
    {
        return $this->hasMany(QuoteSection::class)->orderBy('sort_order');
    }

    /**
     * @return HasMany<QuoteLineItem, $this>
     */
    public function lineItems(): HasMany
    {
        return $this->hasMany(QuoteLineItem::class)->orderBy('sort_order');
    }

    /**
     * @return HasMany<QuoteActivity, $this>
     */
    public function activities(): HasMany
    {
        return $this->hasMany(QuoteActivity::class)->latest();
    }

    /**
     * @return HasOne<QuoteShortCode, $this>
     */
    public function shortCode(): HasOne
    {
        return $this->hasOne(QuoteShortCode::class);
    }

    /**
     * @return HasMany<QuoteFollowUp, $this>
     */
    public function quoteFollowUps(): HasMany
    {
        return $this->hasMany(QuoteFollowUp::class)->orderBy('scheduled_at');
    }

    /**
     * @return HasMany<QuoteTrackingEvent, $this>
     */
    public function trackingEvents(): HasMany
    {
        return $this->hasMany(QuoteTrackingEvent::class)->orderByDesc('occurred_at');
    }

    /**
     * @return HasMany<QuoteMessage, $this>
     */
    public function messages(): HasMany
    {
        return $this->hasMany(QuoteMessage::class)->latest();
    }

    /**
     * @return HasOne<QuoteWinProbability, $this>
     */
    public function winProbability(): HasOne
    {
        return $this->hasOne(QuoteWinProbability::class);
    }

    protected function getSignatureUrlAttribute(): ?string
    {
        return $this->signature_path ? Storage::url($this->signature_path) : null;
    }

    /**
     * @return HasMany<QuoteApproval, $this>
     */
    public function quoteApprovals(): HasMany
    {
        return $this->hasMany(QuoteApproval::class);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => QuoteStatus::class,
            'valid_until' => 'date',
            'layout_snapshot' => 'array',
            'subtotal' => 'decimal:2',
            'discount_amount' => 'decimal:2',
            'tax_amount' => 'decimal:2',
            'total' => 'decimal:2',
            'fx_rate' => 'decimal:6',
            'base_total' => 'decimal:2',
            'requires_deposit' => 'boolean',
            'deposit_amount' => 'decimal:2',
            'approval_granted' => 'boolean',
            'approval_granted_at' => 'datetime',
            'sent_at' => 'datetime',
            'viewed_at' => 'datetime',
            'accepted_at' => 'datetime',
            'declined_at' => 'datetime',
            'won_at' => 'datetime',
            'lost_at' => 'datetime',
            'view_count' => 'integer',
            'time_spent_seconds' => 'integer',
            'deleted_at' => 'datetime',
        ];
    }
}

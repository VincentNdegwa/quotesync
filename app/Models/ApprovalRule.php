<?php

namespace App\Models;

use App\Enums\ApprovalRuleTriggerType;
use Database\Factories\ApprovalRuleFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ApprovalRule extends Model
{
    /** @use HasFactory<ApprovalRuleFactory> */
    use HasFactory;

    protected $fillable = [
        'workspace_id',
        'trigger_type',
        'threshold_value',
        'client_id',
        'approver_id',
        'is_active',
    ];

    protected $casts = [
        'threshold_value' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approver_id');
    }

    public function quoteApprovals(): HasMany
    {
        return $this->hasMany(QuoteApproval::class);
    }

    public function matches(Quote $quote): bool
    {
        if (! $this->is_active) {
            return false;
        }

        return match ($this->trigger_type) {
            ApprovalRuleTriggerType::ValueAbove->value => $quote->total >= $this->threshold_value,
            ApprovalRuleTriggerType::ValueBelow->value => $quote->total <= $this->threshold_value,
            ApprovalRuleTriggerType::Client->value => $quote->client_id === $this->client_id,
            ApprovalRuleTriggerType::AllQuotes->value => true,
            default => false,
        };
    }
}

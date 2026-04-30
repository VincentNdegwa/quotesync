<?php

namespace App\Models;

use App\Enums\QuoteApprovalStatus;
use Database\Factories\QuoteApprovalFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QuoteApproval extends Model
{
    /** @use HasFactory<QuoteApprovalFactory> */
    use HasFactory;
    protected $fillable = [
        'quote_id',
        'approval_rule_id',
        'approver_id',
        'status',
        'comment',
        'approved_at',
    ];

    protected $casts = [
        'approved_at' => 'datetime',
    ];

    public function quote(): BelongsTo
    {
        return $this->belongsTo(Quote::class);
    }

    public function approvalRule(): BelongsTo
    {
        return $this->belongsTo(ApprovalRule::class);
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approver_id');
    }

    public function approve(?string $comment = null): void
    {
        $this->update([
            'status' => QuoteApprovalStatus::Approved->value,
            'comment' => $comment,
            'approved_at' => now(),
        ]);
    }

    public function reject(?string $comment = null): void
    {
        $this->update([
            'status' => QuoteApprovalStatus::Rejected->value,
            'comment' => $comment,
        ]);
    }
}

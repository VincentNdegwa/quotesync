<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QuoteMessage extends Model
{
    protected $fillable = [
        'quote_id',
        'sender_id',
        'portal_user_id',
        'message',
        'sender_type',
        'is_internal',
    ];

    protected $casts = [
        'is_internal' => 'boolean',
    ];

    protected $appends = [
        'sender_name',
    ];

    public function quote(): BelongsTo
    {
        return $this->belongsTo(Quote::class);
    }

    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    public function portalUser(): BelongsTo
    {
        return $this->belongsTo(PortalUser::class);
    }

    public function getSenderNameAttribute(): string
    {
        if ($this->sender_type === 'portal_user' && $this->portalUser) {
            return $this->portalUser->name;
        }

        if ($this->sender) {
            return $this->sender->name;
        }

        return 'Unknown';
    }

    public function scopeForQuote($query, $quoteId)
    {
        return $query->where('quote_id', $quoteId);
    }

    public function scopeVisibleToPortal($query)
    {
        return $query->where('is_internal', false);
    }
}

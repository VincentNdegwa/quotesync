<?php

namespace App\Models;

use App\Enums\FollowUpChannel;
use App\Enums\InvoiceReminderType;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'invoice_reminder_sequence_id',
    'day_offset',
    'channel',
    'reminder_type',
    'subject',
    'message_template',
    'send_automatically',
    'sort_order',
])]
class InvoiceReminderStep extends Model
{
    /**
     * @return BelongsTo<InvoiceReminderSequence, $this>
     */
    public function sequence(): BelongsTo
    {
        return $this->belongsTo(InvoiceReminderSequence::class, 'invoice_reminder_sequence_id');
    }

    /**
     * @return HasMany<InvoiceReminder, $this>
     */
    public function invoiceReminders(): HasMany
    {
        return $this->hasMany(InvoiceReminder::class);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'day_offset' => 'integer',
            'channel' => FollowUpChannel::class,
            'reminder_type' => InvoiceReminderType::class,
            'send_automatically' => 'boolean',
            'sort_order' => 'integer',
        ];
    }
}

<?php

namespace App\Services\Quotes;

use App\Models\Quote;
use App\Models\QuoteMessage;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;

class QuoteMessageService
{
    public function getMessagesForQuote(Quote $quote, bool $internalOnly = false): Collection
    {
        $query = $quote->messages()->with(['sender:id,name', 'portalUser:id,name']);

        if ($internalOnly) {
            // $query->where('is_internal', true);
        }

        return $query->orderBy('created_at', 'asc')->get();
    }

    public function createMessage(Quote $quote, string $message, ?string $senderType = null): QuoteMessage
    {
        $senderId = null;
        $portalUserId = null;
        $isInternal = false;

        if ($senderType === 'portal_user') {
            $portalUser = Auth::guard('portal')->user();
            $portalUserId = $portalUser?->id;
            $isInternal = false;
        } else {
            $user = Auth::user();
            $senderId = $user?->id;
            $isInternal = true;
        }

        return QuoteMessage::create([
            'quote_id' => $quote->id,
            'sender_id' => $senderId,
            'portal_user_id' => $portalUserId,
            'message' => $message,
            'sender_type' => $senderType ?? 'user',
            'is_internal' => $isInternal,
        ]);
    }

    public function markAsRead(Quote $quote): void
    {
        // Future implementation for read receipts
    }
}

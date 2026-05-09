<?php

namespace App\Traits;

use App\Models\Quote;

trait ResolvesClientState
{
    protected function resolveClientState(Quote $quote): string
    {
        // State 2 — Accepted (client formally signed)
        if ($quote->accepted_at) {
            return 'accepted';
        }

        // State 3 — No longer available
        if (in_array($quote->status->value, ['declined', 'expired', 'lost'])) {
            return 'closed';
        }

        // State 1 — Open (default)
        // Shown when: draft, sent, viewed, pending_approval, won (manual without accepted_at)
        return 'open';
    }
}

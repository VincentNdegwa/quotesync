<?php

namespace App\Services;

use App\Models\Quote;

class WinProbabilityService
{
    public function calculate(Quote $quote): float
    {
        $score = 50.0; // base

        // Signal 1: Client's acceptance history
        $clientWinRate = $this->clientWinRate($quote->client_id, $quote->workspace_id);
        $score += ($clientWinRate - 0.5) * 30;

        // Signal 2: View count
        if ($quote->view_count >= 4) $score += 15;
        elseif ($quote->view_count >= 2) $score += 8;
        elseif ($quote->view_count === 0 && $this->daysSinceSent($quote) > 2) $score -= 10;

        // Signal 3: Time spent reading
        $minutes = ($quote->time_spent_seconds ?? 0) / 60;
        if ($minutes >= 5) $score += 12;
        elseif ($minutes >= 2) $score += 6;

        // Signal 4: Days since sent
        $daysSent = $this->daysSinceSent($quote);
        if ($daysSent <= 2) $score += 5;
        elseif ($daysSent > 7) $score -= 8;
        elseif ($daysSent > 14) $score -= 18;

        // Signal 5: Quote value vs client's average
        $avgDeal = $this->clientAverageDealSize($quote->client_id);
        if ($avgDeal > 0) {
            $ratio = $quote->total / $avgDeal;
            if ($ratio > 2.0) $score -= 12;
            elseif ($ratio < 0.7) $score += 8;
        }

        // Signal 6: Discount given
        if ($quote->discount_amount > 0) {
            $discountPct = $quote->discount_amount / $quote->subtotal * 100;
            if ($discountPct > 20) $score -= 10;
            elseif ($discountPct > 10) $score -= 4;
        }

        // Signal 7: Template win rate
        if ($quote->template_id) {
            $templateWinRate = $this->templateWinRate($quote->template_id);
            $score += ($templateWinRate - 0.5) * 15;
        }

        return min(95, max(5, round($score, 2)));
    }

    private function clientWinRate(?int $clientId, int $workspaceId): float
    {
        if (!$clientId) return 0.5;

        $total = Quote::where('client_id', $clientId)
            ->where('workspace_id', $workspaceId)
            ->whereIn('status', ['won', 'lost'])
            ->count();

        if ($total === 0) return 0.5;

        $won = Quote::where('client_id', $clientId)
            ->where('workspace_id', $workspaceId)
            ->where('status', 'won')
            ->count();

        return $won / $total;
    }

    private function daysSinceSent(Quote $quote): int
    {
        if (!$quote->sent_at) return 0;
        return $quote->sent_at->diffInDays(now());
    }

    private function clientAverageDealSize(?int $clientId): float
    {
        if (!$clientId) return 0;

        return Quote::where('client_id', $clientId)
            ->where('status', 'won')
            ->avg('total') ?? 0;
    }

    private function templateWinRate(?int $templateId): float
    {
        if (!$templateId) return 0.5;

        $total = Quote::where('template_id', $templateId)
            ->whereIn('status', ['won', 'lost'])
            ->count();

        if ($total === 0) return 0.5;

        $won = Quote::where('template_id', $templateId)
            ->where('status', 'won')
            ->count();

        return $won / $total;
    }
}

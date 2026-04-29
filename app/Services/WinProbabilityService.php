<?php

namespace App\Services;

use App\Models\Quote;
use Illuminate\Support\Collection;

class WinProbabilityService
{
    private const MIN_SAMPLE_SIZE = 5;
    private const DEFAULT_RATE    = 0.50;

    public function calculate(Quote $quote): array
    {
        $signals = $this->gatherSignals($quote);

        if ($signals->isEmpty()) {
            return $this->noDataResult();
        }

        $weightedSum   = $signals->sum(fn ($s) => $s['probability'] * $s['weight']);
        $totalWeight   = $signals->sum(fn ($s) => $s['weight']);
        $probability   = $totalWeight > 0 ? $weightedSum / $totalWeight : self::DEFAULT_RATE;

        $probability   = min(0.92, max(0.05, $probability));

        $confidence    = $this->confidence($signals);

        return [
            'probability'   => round($probability * 100),
            'confidence'    => $confidence,
            'signals'       => $signals->toArray(),
            'has_data'      => true,
        ];
    }

    private function gatherSignals(Quote $quote): Collection
    {
        $signals = collect();

        // ── Signal 1: Workspace historical win rate ──────────────────────
        // The baseline. How often does this workspace win any quote at all.
        $workspaceRate = $this->workspaceWinRate($quote->workspace_id);
        if ($workspaceRate !== null) {
            $signals->push([
                'key'         => 'workspace_baseline',
                'label'       => 'Your overall win rate',
                'probability' => $workspaceRate['rate'],
                'weight'      => min(3.0, $workspaceRate['count'] / 20),
                'sample_size' => $workspaceRate['count'],
                'direction'   => $workspaceRate['rate'] >= 0.5 ? 'positive' : 'negative',
            ]);
        }

        // ── Signal 2: Client-specific win rate ───────────────────────────
        // How often does this workspace win quotes for THIS client.
        $clientRate = $this->clientWinRate($quote->client_id, $quote->workspace_id);
        if ($clientRate !== null) {
            $signals->push([
                'key'         => 'client_history',
                'label'       => 'Win rate with this client',
                'probability' => $clientRate['rate'],
                'weight'      => min(4.0, $clientRate['count'] / 3),
                'sample_size' => $clientRate['count'],
                'direction'   => $clientRate['rate'] >= 0.5 ? 'positive' : 'negative',
            ]);
        }

        // ── Signal 3: Engagement — view count ───────────────────────────
        // Based on what percentage of won quotes had this many views.
        $engagementSignal = $this->engagementSignal(
            $quote->view_count ?? 0,
            $quote->workspace_id
        );
        if ($engagementSignal !== null) {
            $signals->push($engagementSignal);
        }

        // ── Signal 4: Time decay ─────────────────────────────────────────
        // Based on historical data: quotes closed after X days win at what rate.
        $decaySignal = $this->timeDecaySignal($quote);
        if ($decaySignal !== null) {
            $signals->push($decaySignal);
        }

        // ── Signal 5: Value ratio vs client average ──────────────────────
        $valueSignal = $this->valueRatioSignal($quote);
        if ($valueSignal !== null) {
            $signals->push($valueSignal);
        }

        return $signals;
    }

    private function workspaceWinRate(int $workspaceId): ?array
    {
        $resolved = Quote::where('workspace_id', $workspaceId)
            ->whereIn('status', ['won', 'lost'])
            ->count();

        if ($resolved < self::MIN_SAMPLE_SIZE) {
            return null;
        }

        $won = Quote::where('workspace_id', $workspaceId)
            ->where('status', 'won')
            ->count();

        return [
            'rate'  => $won / $resolved,
            'count' => $resolved,
        ];
    }

    private function clientWinRate(?int $clientId, int $workspaceId): ?array
    {
        if (! $clientId) {
            return null;
        }

        $resolved = Quote::where('client_id', $clientId)
            ->where('workspace_id', $workspaceId)
            ->whereIn('status', ['won', 'lost'])
            ->count();

        if ($resolved < 2) {
            return null;
        }

        $won = Quote::where('client_id', $clientId)
            ->where('workspace_id', $workspaceId)
            ->where('status', 'won')
            ->count();

        return [
            'rate'  => $won / $resolved,
            'count' => $resolved,
        ];
    }

    private function engagementSignal(int $viewCount, int $workspaceId): ?array
    {
        // Compare this quote's view count to the workspace average view count
        // at time of winning vs at time of losing
        $avgViewsOnWon = Quote::where('workspace_id', $workspaceId)
            ->where('status', 'won')
            ->avg('view_count') ?? 0;

        $avgViewsOnLost = Quote::where('workspace_id', $workspaceId)
            ->where('status', 'lost')
            ->avg('view_count') ?? 0;

        $totalResolved = Quote::where('workspace_id', $workspaceId)
            ->whereIn('status', ['won', 'lost'])
            ->count();

        if ($totalResolved < self::MIN_SAMPLE_SIZE || $avgViewsOnWon === $avgViewsOnLost) {
            return null;
        }

        // Position this quote's views between the lost average and won average
        // 0 views against won avg of 3 → below average → lower probability
        if ($avgViewsOnWon > $avgViewsOnLost) {
            $range    = $avgViewsOnWon - $avgViewsOnLost;
            $position = min(1.0, max(0.0, ($viewCount - $avgViewsOnLost) / $range));
        } else {
            $position = $viewCount > 0 ? 0.6 : 0.4;
        }

        return [
            'key'         => 'engagement',
            'label'       => 'Client engagement (views)',
            'probability' => $position,
            'weight'      => 1.5,
            'sample_size' => $totalResolved,
            'direction'   => $position >= 0.5 ? 'positive' : 'negative',
            'meta'        => [
                'view_count'       => $viewCount,
                'avg_views_won'    => round($avgViewsOnWon, 1),
                'avg_views_lost'   => round($avgViewsOnLost, 1),
            ],
        ];
    }

    private function timeDecaySignal(Quote $quote): ?array
    {
        if (! $quote->sent_at) {
            return null;
        }

        $daysSinceSent = $quote->sent_at->diffInDays(now());

        $workspaceId = $quote->workspace_id;

        // Find the median days-to-close for won quotes in this workspace
        $avgDaysToClose = Quote::where('workspace_id', $workspaceId)
            ->where('status', 'won')
            ->whereNotNull('sent_at')
            ->whereNotNull('won_at')
            ->selectRaw('AVG(DATEDIFF(won_at, sent_at)) as avg_days')
            ->value('avg_days') ?? 7;

        $totalResolved = Quote::where('workspace_id', $workspaceId)
            ->whereIn('status', ['won', 'lost'])
            ->count();

        if ($totalResolved < self::MIN_SAMPLE_SIZE) {
            return null;
        }

        // Win rate for quotes closed within avg days vs beyond it
        $fastWins = Quote::where('workspace_id', $workspaceId)
            ->where('status', 'won')
            ->whereNotNull('sent_at')
            ->whereNotNull('won_at')
            ->whereRaw('DATEDIFF(won_at, sent_at) <= ?', [$avgDaysToClose])
            ->count();

        $fastTotal = Quote::where('workspace_id', $workspaceId)
            ->whereIn('status', ['won', 'lost'])
            ->whereNotNull('sent_at')
            ->whereRaw('DATEDIFF(COALESCE(won_at, lost_at), sent_at) <= ?', [$avgDaysToClose])
            ->count();

        $fastRate = $fastTotal > 0 ? $fastWins / $fastTotal : 0.5;
        $slowRate = 1 - $fastRate;

        $probability = $daysSinceSent <= $avgDaysToClose ? $fastRate : $slowRate;

        return [
            'key'         => 'time_decay',
            'label'       => 'Time since sent',
            'probability' => $probability,
            'weight'      => 1.0,
            'sample_size' => $totalResolved,
            'direction'   => $probability >= 0.5 ? 'positive' : 'negative',
            'meta'        => [
                'days_since_sent'    => $daysSinceSent,
                'avg_days_to_close'  => round($avgDaysToClose, 1),
            ],
        ];
    }

    private function valueRatioSignal(Quote $quote): ?array
    {
        if (! $quote->client_id || ! $quote->base_total) {
            return null;
        }

        $avgWonValue = Quote::where('client_id', $quote->client_id)
            ->where('workspace_id', $quote->workspace_id)
            ->where('status', 'won')
            ->avg('base_total') ?? 0;

        if ($avgWonValue <= 0) {
            return null;
        }

        $ratio = $quote->base_total / $avgWonValue;

        // Quotes near the client's historical average close better
        // Much higher or much lower than average is unusual and riskier
        if ($ratio >= 0.7 && $ratio <= 1.5) {
            $probability = 0.65;
        } elseif ($ratio > 1.5 && $ratio <= 2.5) {
            $probability = 0.40;
        } elseif ($ratio > 2.5) {
            $probability = 0.25;
        } elseif ($ratio < 0.7) {
            $probability = 0.55;
        } else {
            $probability = 0.50;
        }

        return [
            'key'         => 'value_ratio',
            'label'       => 'Quote value vs client average',
            'probability' => $probability,
            'weight'      => 0.8,
            'sample_size' => 1,
            'direction'   => $probability >= 0.5 ? 'positive' : 'negative',
            'meta'        => [
                'quote_value'     => $quote->base_total,
                'client_average'  => round($avgWonValue, 2),
                'ratio'           => round($ratio, 2),
            ],
        ];
    }

    private function confidence(Collection $signals): string
    {
        $totalWeight = $signals->sum(fn ($s) => $s['weight']);
        $totalSamples = $signals->sum(fn ($s) => $s['sample_size']);

        if ($totalWeight < 1.0 || $totalSamples < 10) {
            return 'low';
        }

        if ($totalWeight < 3.0 || $totalSamples < 30) {
            return 'medium';
        }

        return 'high';
    }

    private function noDataResult(): array
    {
        return [
            'probability' => null,
            'confidence'  => 'none',
            'signals'     => [],
            'has_data'    => false,
        ];
    }
}

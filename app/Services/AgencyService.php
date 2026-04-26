<?php

namespace App\Services;

use App\Models\Quote;
use App\Models\Workspace;

class AgencyService
{
    public function calculateCommissionForQuote(Quote $quote): float
    {
        $workspace = $quote->workspace;

        if (!$workspace->isAgencyModeEnabled()) {
            return 0;
        }

        return $workspace->calculateAgencyCommission($quote->total);
    }

    public function getAgencyRevenue(Workspace $workspace, ?string $startDate = null, ?string $endDate = null): float
    {
        if (!$workspace->isAgencyModeEnabled()) {
            return 0;
        }

        $query = Quote::where('workspace_id', $workspace->id)
            ->whereIn('status', ['accepted', 'won']);

        if ($startDate) {
            $query->where('created_at', '>=', $startDate);
        }

        if ($endDate) {
            $query->where('created_at', '<=', $endDate);
        }

        $quotes = $query->get();

        return $quotes->sum(fn ($quote) => $this->calculateCommissionForQuote($quote));
    }

    public function updateAgencySettings(Workspace $workspace, array $data): void
    {
        $workspace->update([
            'agency_mode_enabled' => $data['enabled'] ?? false,
            'agency_commission_rate' => $data['commission_rate'] ?? null,
            'agency_commission_type' => $data['commission_type'] ?? 'percentage',
        ]);
    }

    public function getAgencyStats(Workspace $workspace): array
    {
        if (!$workspace->isAgencyModeEnabled()) {
            return [
                'enabled' => false,
                'total_revenue' => 0,
                'total_quotes' => 0,
                'commission_rate' => 0,
                'commission_type' => 'percentage',
            ];
        }

        $quotes = Quote::where('workspace_id', $workspace->id)
            ->whereIn('status', ['accepted', 'won'])
            ->get();

        $totalRevenue = $quotes->sum('total');
        $totalCommission = $quotes->sum(fn ($quote) => $this->calculateCommissionForQuote($quote));

        return [
            'enabled' => true,
            'total_revenue' => $totalRevenue,
            'total_commission' => $totalCommission,
            'total_quotes' => $quotes->count(),
            'commission_rate' => $workspace->getAgencyCommissionRate(),
            'commission_type' => $workspace->getAgencyCommissionType(),
        ];
    }
}

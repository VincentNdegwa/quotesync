<?php

namespace App\Http\Controllers;

use App\Models\Quote;
use App\Models\Workspace;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class AnalyticsController extends Controller
{
    public function index(Request $request): Response
    {
        $workspace = $request->user()?->currentWorkspace;
        abort_unless($workspace instanceof Workspace, 403);

        $startDate = $request->get('start_date', now()->subMonths(3)->toDateString());
        $endDate = $request->get('end_date', now()->endOfDay()->toDateTimeString());

        // Calculate previous period for trend comparison
        $periodStart = \Carbon\Carbon::parse($startDate);
        $periodEnd = \Carbon\Carbon::parse($endDate);
        $periodDuration = $periodStart->diffInDays($periodEnd);
        
        $previousStartDate = $periodStart->subDays($periodDuration)->toDateString();
        $previousEndDate = $periodEnd->subDays($periodDuration)->endOfDay()->toDateTimeString();

        // Revenue & Pipeline - Current Period
        $totalRevenue = Quote::where('workspace_id', $workspace->id)
            ->where('status', 'won')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->sum('total');

        $pipelineValue = Quote::where('workspace_id', $workspace->id)
            ->whereIn('status', ['sent', 'viewed'])
            ->whereBetween('created_at', [$startDate, $endDate])
            ->sum('total');

        $quotesSent = Quote::where('workspace_id', $workspace->id)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->count();

        $quotesWon = Quote::where('workspace_id', $workspace->id)
            ->where('status', 'won')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->count();

        $quotesLost = Quote::where('workspace_id', $workspace->id)
            ->where('status', 'lost')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->count();

        $winRate = $quotesSent > 0 ? ($quotesWon / $quotesSent) * 100 : 0;

        // Revenue & Pipeline - Previous Period (for trends)
        $previousTotalRevenue = Quote::where('workspace_id', $workspace->id)
            ->where('status', 'won')
            ->whereBetween('created_at', [$previousStartDate, $previousEndDate])
            ->sum('total');

        $previousPipelineValue = Quote::where('workspace_id', $workspace->id)
            ->whereIn('status', ['sent', 'viewed'])
            ->whereBetween('created_at', [$previousStartDate, $previousEndDate])
            ->sum('total');

        $previousQuotesSent = Quote::where('workspace_id', $workspace->id)
            ->whereBetween('created_at', [$previousStartDate, $previousEndDate])
            ->count();

        $previousQuotesWon = Quote::where('workspace_id', $workspace->id)
            ->where('status', 'won')
            ->whereBetween('created_at', [$previousStartDate, $previousEndDate])
            ->count();

        $previousQuotesLost = Quote::where('workspace_id', $workspace->id)
            ->where('status', 'lost')
            ->whereBetween('created_at', [$previousStartDate, $previousEndDate])
            ->count();

        $previousWinRate = $previousQuotesSent > 0 ? ($previousQuotesWon / $previousQuotesSent) * 100 : 0;

        // Calculate trends (percentage change)
        $totalRevenueTrend = $previousTotalRevenue > 0 
            ? round((($totalRevenue - $previousTotalRevenue) / $previousTotalRevenue) * 100, 1) 
            : null;
        
        $pipelineValueTrend = $previousPipelineValue > 0 
            ? round((($pipelineValue - $previousPipelineValue) / $previousPipelineValue) * 100, 1) 
            : null;
        
        $quotesSentTrend = $previousQuotesSent > 0 
            ? round((($quotesSent - $previousQuotesSent) / $previousQuotesSent) * 100, 1) 
            : null;
        
        $quotesWonTrend = $previousQuotesWon > 0 
            ? round((($quotesWon - $previousQuotesWon) / $previousQuotesWon) * 100, 1) 
            : null;
        
        $quotesLostTrend = $previousQuotesLost > 0 
            ? round((($quotesLost - $previousQuotesLost) / $previousQuotesLost) * 100, 1) 
            : null;
        
        $winRateTrend = $previousWinRate > 0 
            ? round(($winRate - $previousWinRate), 1) 
            : null;

        $dateFormat = DB::connection()->getDriverName() === 'sqlite'
            ? "strftime('%Y-%m', created_at)"
            : "DATE_FORMAT(created_at, '%Y-%m')";

        $winRateByMonth = Quote::where('workspace_id', $workspace->id)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->selectRaw("$dateFormat as month,
                SUM(CASE WHEN status = 'won' THEN 1 ELSE 0 END) as won,
                COUNT(*) as total")
            ->groupBy('month')
            ->orderBy('month')
            ->get()
            ->map(fn ($item) => [
                'month' => $item->month,
                'rate' => $item->total > 0 ? ($item->won / $item->total) * 100 : 0,
            ]);

        // Decline reasons
        $declineReasons = Quote::where('workspace_id', $workspace->id)
            ->where('status', 'lost')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->whereNotNull('decline_reason')
            ->selectRaw('decline_reason, COUNT(*) as count')
            ->groupBy('decline_reason')
            ->orderByDesc('count')
            ->get();

        // Top templates
        $topTemplates = Quote::where('workspace_id', $workspace->id)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->whereNotNull('template_id')
            ->with('template')
            ->selectRaw('template_id, 
                COUNT(*) as total_quotes,
                SUM(CASE WHEN status = "won" THEN 1 ELSE 0 END) as won_quotes,
                AVG(total) as avg_value')
            ->groupBy('template_id')
            ->orderByDesc('won_quotes')
            ->limit(10)
            ->get();

        return Inertia::render('analytics/Index', [
            'stats' => [
                'total_revenue' => $totalRevenue,
                'pipeline_value' => $pipelineValue,
                'quotes_sent' => $quotesSent,
                'quotes_won' => $quotesWon,
                'quotes_lost' => $quotesLost,
                'win_rate' => round($winRate, 2),
                'trends' => [
                    'total_revenue' => $totalRevenueTrend,
                    'pipeline_value' => $pipelineValueTrend,
                    'quotes_sent' => $quotesSentTrend,
                    'quotes_won' => $quotesWonTrend,
                    'quotes_lost' => $quotesLostTrend,
                    'win_rate' => $winRateTrend,
                ],
            ],
            'charts' => [
                'win_rate_by_month' => $winRateByMonth,
                'decline_reasons' => $declineReasons,
                'top_templates' => $topTemplates,
                'win_rate_by_team_member' => [],
                'loss_by_value_range' => [],
                'average_days' => ['days_to_win' => 0, 'days_to_lose' => 0],
            ],
            'filters' => [
                'start_date' => $startDate,
                'end_date' => $endDate,
            ],
        ]);
    }
}

<?php

namespace App\Http\Controllers;

use App\Enums\QuoteStatus;
use App\Models\Quote;
use App\Models\Workspace;
use Illuminate\Http\Request;
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
        $teamMemberId = $request->get('team_member');

        // Section 1: Revenue Intelligence
        $wonRevenue = Quote::where('workspace_id', $workspace->id)
            ->where('status', 'accepted')
            ->whereBetween('accepted_at', [$startDate, $endDate])
            ->sum('total');

        $lostRevenue = Quote::where('workspace_id', $workspace->id)
            ->where('status', 'declined')
            ->whereBetween('declined_at', [$startDate, $endDate])
            ->sum('total');

        $stillOpen = Quote::where('workspace_id', $workspace->id)
            ->whereIn('status', ['sent', 'viewed'])
            ->whereBetween('created_at', [$startDate, $endDate])
            ->sum('total');

        $totalQuoted = $wonRevenue + $lostRevenue + $stillOpen;
        $wonPer100 = $totalQuoted > 0 ? round(($wonRevenue / $totalQuoted) * 100) : 0;

        // Revenue trend (12 months)
        $revenueTrend = collect(range(0, 11))
            ->map(function (int $offset) use ($workspace) {
                $date = now()->subMonths(11 - $offset);
                $wonRevenue = Quote::where('workspace_id', $workspace->id)
                    ->where('status', 'accepted')
                    ->whereMonth('accepted_at', $date->month)
                    ->whereYear('accepted_at', $date->year)
                    ->sum('total');

                return [
                    'month' => $date->format('M'),
                    'won' => $wonRevenue,
                ];
            })
            ->values();

        // Rolling 3-month average
        $revenueTrendWithAverage = $revenueTrend->map(function ($item, $index) use ($revenueTrend) {
            $recent3 = $revenueTrend->slice(max(0, $index - 2), 3);
            $average = $recent3->sum('won') / max(1, $recent3->count());
            return array_merge($item, ['average' => $average]);
        });

        // Section 2: Win/Loss Analysis
        $declineReasons = Quote::where('workspace_id', $workspace->id)
            ->where('status', 'declined')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->whereNotNull('decline_reason')
            ->selectRaw('decline_reason, COUNT(*) as count, SUM(total) as total_value')
            ->groupBy('decline_reason')
            ->orderByDesc('count')
            ->get();

        // Time to win histogram
        $timeToWinBuckets = [
            '0-2 days' => 0,
            '3-7 days' => 0,
            '8-14 days' => 0,
            '15+ days' => 0,
            'Never' => 0,
        ];

        Quote::where('workspace_id', $workspace->id)
            ->whereNotNull('sent_at')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->get()
            ->each(function (Quote $quote) use (&$timeToWinBuckets): void {
                $status = $quote->status instanceof QuoteStatus ? $quote->status->value : (string) $quote->status;

                if ($status === 'accepted' && $quote->accepted_at instanceof \Illuminate\Support\Carbon) {
                    $days = $quote->sent_at instanceof \Illuminate\Support\Carbon
                        ? $quote->sent_at->diffInDays($quote->accepted_at)
                        : 0;

                    if ($days <= 2) {
                        $timeToWinBuckets['0-2 days']++;
                    } elseif ($days <= 7) {
                        $timeToWinBuckets['3-7 days']++;
                    } elseif ($days <= 14) {
                        $timeToWinBuckets['8-14 days']++;
                    } else {
                        $timeToWinBuckets['15+ days']++;
                    }

                    return;
                }

                if (in_array($status, ['declined', 'expired'], true)) {
                    $timeToWinBuckets['Never']++;
                }
            });

        $timeToWin = collect($timeToWinBuckets)
            ->map(fn (int $count, string $range): array => [
                'range' => $range,
                'count' => $count,
            ])
            ->filter(fn (array $item): bool => $item['count'] > 0)
            ->values();

        // Loss reasons (derived from decline reasons)
        $lossReasons = $declineReasons
            ->map(function ($reason) {
                return [
                    'reason' => $reason->decline_reason,
                    'count' => (int) $reason->count,
                    'total_value' => (float) ($reason->total_value ?? 0),
                ];
            })
            ->values();

        // Section 3: Quote Performance
        // By template
        $templatePerformance = Quote::where('workspace_id', $workspace->id)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->whereNotNull('template_id')
            ->with('template')
            ->selectRaw('template_id, 
                COUNT(*) as total_quotes,
                SUM(CASE WHEN status = "accepted" THEN 1 ELSE 0 END) as won_quotes,
                AVG(total) as avg_value')
            ->groupBy('template_id')
            ->get()
            ->map(function ($quote) {
                return [
                    'template_id' => $quote->template_id,
                    'template_name' => $quote->template?->name ?? 'Unknown',
                    'win_rate' => $quote->total_quotes > 0 ? round(($quote->won_quotes / $quote->total_quotes) * 100) : 0,
                    'total_quotes' => (int) $quote->total_quotes,
                    'avg_value' => (float) ($quote->avg_value ?? 0),
                ];
            })
            ->values();

        // By deal size range
        $dealSizePerformance = [
            ['range' => 'Under KES 50k', 'min' => 0, 'max' => 50000],
            ['range' => 'KES 50k - 200k', 'min' => 50000, 'max' => 200000],
            ['range' => 'KES 200k - 500k', 'min' => 200000, 'max' => 500000],
            ['range' => 'Over KES 500k', 'min' => 500000, 'max' => PHP_INT_MAX],
        ];

        $dealSizeData = collect($dealSizePerformance)->map(function ($range) use ($workspace, $startDate, $endDate) {
            $quotes = Quote::where('workspace_id', $workspace->id)
                ->whereBetween('created_at', [$startDate, $endDate])
                ->whereBetween('total', [$range['min'], $range['max']])
                ->get();

            $wonCount = $quotes->where('status', 'accepted')->count();
            $totalCount = $quotes->count();

            return [
                'range' => $range['range'],
                'win_rate' => $totalCount > 0 ? round(($wonCount / $totalCount) * 100) : 0,
            ];
        });

        // By discount given
        $discountPerformance = [
            ['range' => 'No discount', 'min' => 0, 'max' => 0],
            ['range' => '1-10% discount', 'min' => 1, 'max' => 10],
            ['range' => '11-20% discount', 'min' => 11, 'max' => 20],
            ['range' => 'Over 20% discount', 'min' => 21, 'max' => 100],
        ];

        $discountQuotes = Quote::where('workspace_id', $workspace->id)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->whereNotNull('subtotal')
            ->get();

        $discountData = collect($discountPerformance)->map(function ($range) use ($discountQuotes) {
            $quotes = $discountQuotes->filter(function (Quote $quote) use ($range): bool {
                $subtotal = (float) ($quote->subtotal ?? 0);
                $discountAmount = (float) ($quote->discount_amount ?? 0);
                $discountPercent = $subtotal > 0 ? ($discountAmount / $subtotal) * 100 : 0;

                return match ($range['range']) {
                    'No discount' => $discountPercent === 0,
                    '1-10% discount' => $discountPercent > 0 && $discountPercent <= 10,
                    '11-20% discount' => $discountPercent > 10 && $discountPercent <= 20,
                    default => $discountPercent > 20,
                };
            });

            $wonCount = $quotes->where('status', 'accepted')->count();
            $totalCount = $quotes->count();

            return [
                'range' => $range['range'],
                'win_rate' => $totalCount > 0 ? round(($wonCount / $totalCount) * 100) : 0,
            ];
        });

        // Section 4: Client Intelligence
        $clientPerformance = Quote::where('workspace_id', $workspace->id)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->whereNotNull('client_id')
            ->with('client:id,company_name')
            ->get()
            ->groupBy('client_id')
            ->map(function ($quotes) {
                /** @var \Illuminate\Support\Collection<int, Quote> $quotes */
                $wonQuotes = $quotes->filter(function (Quote $quote): bool {
                    return $quote->status instanceof QuoteStatus
                        ? $quote->status === QuoteStatus::Accepted
                        : $quote->status === QuoteStatus::Accepted->value;
                });

                $responseTimes = $quotes
                    ->filter(function (Quote $quote): bool {
                        return $quote->sent_at !== null && $quote->accepted_at !== null;
                    })
                    ->map(function (Quote $quote): int {
                        return $quote->sent_at->diffInDays($quote->accepted_at);
                    });

                $quote = $quotes->first();

                return [
                    'client_id' => (int) $quote->client_id,
                    'client_name' => $quote->client?->company_name ?? 'Unknown',
                    'quotes_count' => $quotes->count(),
                    'won_count' => $wonQuotes->count(),
                    'win_rate' => $quotes->count() > 0 ? round(($wonQuotes->count() / $quotes->count()) * 100) : 0,
                    'total_won' => (float) $wonQuotes->sum('total'),
                    'avg_response_days' => round((float) ($responseTimes->avg() ?? 0), 1),
                ];
            })
            ->sortByDesc('total_won')
            ->take(20)
            ->values();

        // Section 5: Currency Breakdown
        $currencyBreakdown = Quote::where('workspace_id', $workspace->id)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->selectRaw('currency, 
                COUNT(*) as quotes_sent,
                SUM(CASE WHEN status = "accepted" THEN total ELSE 0 END) as won_revenue,
                SUM(CASE WHEN status IN ("sent", "viewed") THEN total ELSE 0 END) as pipeline,
                1 as avg_rate')
            ->groupBy('currency')
            ->get()
            ->map(function ($quote) {
                return [
                    'currency' => $quote->currency,
                    'quotes_sent' => (int) $quote->quotes_sent,
                    'won_revenue' => (float) ($quote->won_revenue ?? 0),
                    'pipeline' => (float) ($quote->pipeline ?? 0),
                    'avg_rate' => (float) ($quote->avg_rate ?? 1),
                ];
            })
            ->values();

        // Section 6: Forecast
        $openPipeline = Quote::where('workspace_id', $workspace->id)
            ->whereIn('status', ['sent', 'viewed'])
            ->sum('total');

        $winRate90Days = Quote::where('workspace_id', $workspace->id)
            ->where('status', 'accepted')
            ->where('accepted_at', '>=', now()->subDays(90))
            ->count() / max(1, Quote::where('workspace_id', $workspace->id)
                ->where('created_at', '>=', now()->subDays(90))
                ->count());

        $expectedToClose = $openPipeline * $winRate90Days;
        $bestCase = $openPipeline * 0.8;
        $worstCase = $openPipeline * 0.3;

        return Inertia::render('analytics/Index', [
            'revenue_intelligence' => [
                'won_revenue' => $wonRevenue,
                'lost_revenue' => $lostRevenue,
                'still_open' => $stillOpen,
                'won_per_100' => $wonPer100,
                'revenue_trend' => $revenueTrendWithAverage,
            ],
            'win_loss_analysis' => [
                'decline_reasons' => $declineReasons,
                'time_to_win' => $timeToWin,
                'loss_reasons' => $lossReasons,
            ],
            'quote_performance' => [
                'by_template' => $templatePerformance,
                'by_deal_size' => $dealSizeData,
                'by_discount' => $discountData,
            ],
            'client_intelligence' => $clientPerformance,
            'currency_breakdown' => $currencyBreakdown,
            'forecast' => [
                'open_pipeline' => $openPipeline,
                'win_rate_90_days' => round($winRate90Days * 100),
                'expected_to_close' => $expectedToClose,
                'best_case' => $bestCase,
                'worst_case' => $worstCase,
            ],
            'filters' => [
                'start_date' => $startDate,
                'end_date' => $endDate,
                'team_member_id' => $teamMemberId,
            ],
        ]);
    }
}

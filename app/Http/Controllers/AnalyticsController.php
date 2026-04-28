<?php

namespace App\Http\Controllers;

use App\Enums\QuoteStatus;
use App\Models\Quote;
use App\Models\Workspace;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Inertia\Response;

class AnalyticsController extends Controller
{
    private Workspace $workspace;

    public function index(Request $request): Response
    {
        $workspace = $request->user()?->currentWorkspace;
        abort_unless($workspace instanceof Workspace, 403);

        $this->workspace = $workspace;

        $startDateInput = $request->get('start_date');
        $endDateInput = $request->get('end_date');

        $startDate = $startDateInput
            ? Carbon::parse($startDateInput)->startOfDay()
            : Carbon::now()->subMonths(3)->startOfDay();

        $endDate = $endDateInput
            ? Carbon::parse($endDateInput)->endOfDay()
            : Carbon::now()->endOfDay();

        $teamMemberId = $request->get('team_member');

        return Inertia::render('analytics/Index', [
            'revenue_intelligence' => $this->revenueIntelligence($startDate, $endDate),
            'win_loss_analysis' => $this->winLossAnalysis($startDate, $endDate),
            'quote_performance' => $this->quotePerformance($startDate, $endDate),
            'client_intelligence' => $this->clientIntelligence($startDate, $endDate),
            'currency_breakdown' => $this->currencyBreakdown($startDate, $endDate),
            'forecast' => $this->forecast(),
            'filters' => [
                'start_date' => $startDate->toDateString(),
                'end_date' => $endDate->toDateString(),
                'team_member_id' => $teamMemberId,
            ],
        ]);
    }

    private function baseQuery(): Builder
    {
        return Quote::query()->where('workspace_id', $this->workspace->id);
    }

    private function revenueIntelligence(Carbon $startDate, Carbon $endDate): array
    {
        $wonStatuses = QuoteStatus::closedWonStatuses();
        $lostStatuses = QuoteStatus::closedLostStatuses();

        $wonRevenue = (float) $this->baseQuery()
            ->whereIn('status', $wonStatuses)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->sum('total');

        $lostRevenue = (float) $this->baseQuery()
            ->whereIn('status', $lostStatuses)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->sum('total');

        $stillOpen = (float) $this->baseQuery()
            ->whereIn('status', QuoteStatus::pipelineStatuses())
            ->whereBetween('created_at', [$startDate, $endDate])
            ->sum('total');

        $totalQuoted = $wonRevenue + $lostRevenue + $stillOpen;

        return [
            'won_revenue' => $wonRevenue,
            'lost_revenue' => $lostRevenue,
            'still_open' => $stillOpen,
            'won_per_100' => $totalQuoted > 0 ? round(($wonRevenue / $totalQuoted) * 100) : 0,
            'revenue_trend' => $this->revenueTrend()->toArray(),
        ];
    }

    private function revenueTrend(): Collection
    {
        $wonStatuses = QuoteStatus::closedWonStatuses();

        $trend = collect(range(0, 11))
            ->map(function (int $offset) use ($wonStatuses): array {
                $date = Carbon::now()->subMonths(11 - $offset);
                $start = $date->copy()->startOfMonth();
                $end = $date->copy()->endOfMonth();

                $wonRevenue = (float) $this->baseQuery()
                    ->whereIn('status', $wonStatuses)
                    ->whereBetween('created_at', [$start, $end])
                    ->sum('total');

                return [
                    'month' => $date->format('M'),
                    'won' => $wonRevenue,
                ];
            })
            ->values();

        return $trend->map(function (array $item, int $index) use ($trend): array {
            $recent = $trend->slice(max(0, $index - 2), 3);
            $average = $recent->count() > 0
                ? (float) $recent->avg(fn (array $entry) => $entry['won'])
                : 0.0;

            return array_merge($item, ['average' => $average]);
        });
    }

    private function winLossAnalysis(Carbon $startDate, Carbon $endDate): array
    {
        $declineReasons = $this->baseQuery()
            ->where('status', QuoteStatus::Declined->value)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->whereNotNull('decline_reason')
            ->selectRaw('decline_reason, COUNT(*) as count, SUM(total) as total_value')
            ->groupBy('decline_reason')
            ->orderByDesc('count')
            ->get()
            ->map(fn ($row): array => [
                'decline_reason' => $row->decline_reason,
                'count' => (int) $row->count,
                'total_value' => (float) ($row->total_value ?? 0),
            ])
            ->values();

        $timeToWinBuckets = [
            '0-2 days' => 0,
            '3-7 days' => 0,
            '8-14 days' => 0,
            '15+ days' => 0,
            'Never' => 0,
        ];

        $this->baseQuery()
            ->whereNotNull('sent_at')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->get(['status', 'sent_at', 'accepted_at', 'updated_at'])
            ->each(function (Quote $quote) use (&$timeToWinBuckets): void {
                $status = $this->resolveQuoteStatus($quote);

                if (in_array($status, QuoteStatus::closedWonStatuses(), true)) {
                    $closedAt = $quote->accepted_at instanceof Carbon
                        ? $quote->accepted_at
                        : ($quote->updated_at instanceof Carbon ? $quote->updated_at : null);

                    $sentAt = $quote->sent_at instanceof Carbon ? $quote->sent_at : null;

                    if ($closedAt === null || $sentAt === null) {
                        $timeToWinBuckets['Never']++;
                        return;
                    }

                    $days = $sentAt->diffInDays($closedAt);

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

                if (in_array($status, array_merge(QuoteStatus::closedLostStatuses(), [QuoteStatus::Expired->value]), true)) {
                    $timeToWinBuckets['Never']++;
                }
            });

        $timeToWin = collect($timeToWinBuckets)
            ->map(fn (int $count, string $range): array => [
                'range' => $range,
                'count' => $count,
            ])
            ->filter(fn (array $bucket): bool => $bucket['count'] > 0)
            ->values();

        $lossReasons = $declineReasons->map(fn (array $reason): array => [
            'reason' => $reason['decline_reason'],
            'count' => $reason['count'],
            'total_value' => $reason['total_value'],
        ]);

        return [
            'decline_reasons' => $declineReasons->toArray(),
            'time_to_win' => $timeToWin->toArray(),
            'loss_reasons' => $lossReasons->toArray(),
        ];
    }

    private function quotePerformance(Carbon $startDate, Carbon $endDate): array
    {
        return [
            'by_template' => $this->templatePerformance($startDate, $endDate),
            'by_deal_size' => $this->dealSizePerformance($startDate, $endDate),
            'by_discount' => $this->discountPerformance($startDate, $endDate),
        ];
    }

    private function templatePerformance(Carbon $startDate, Carbon $endDate): array
    {
        $wonStatuses = QuoteStatus::closedWonStatuses();

        $quotes = $this->baseQuery()
            ->whereBetween('created_at', [$startDate, $endDate])
            ->whereNotNull('template_id')
            ->with('template:id,name')
            ->get(['template_id', 'status', 'total']);

        return $quotes
            ->groupBy('template_id')
            ->map(function (Collection $group) use ($wonStatuses): array {
                $first = $group->first();

                $totalQuotes = $group->count();
                $wonQuotes = $group->filter(
                    fn (Quote $quote): bool => in_array($this->resolveQuoteStatus($quote), $wonStatuses, true)
                )->count();

                return [
                    'template_id' => (int) ($first?->template_id ?? 0),
                    'template_name' => $first?->template?->name ?? 'Unknown',
                    'win_rate' => $totalQuotes > 0 ? round(($wonQuotes / $totalQuotes) * 100) : 0,
                    'total_quotes' => $totalQuotes,
                    'avg_value' => (float) ($group->avg('total') ?? 0),
                ];
            })
            ->values()
            ->all();
    }

    private function dealSizePerformance(Carbon $startDate, Carbon $endDate): array
    {
        $wonStatuses = QuoteStatus::closedWonStatuses();

        $ranges = [
            ['range' => 'Under KES 50k', 'min' => 0, 'max' => 50000],
            ['range' => 'KES 50k - 200k', 'min' => 50000, 'max' => 200000],
            ['range' => 'KES 200k - 500k', 'min' => 200000, 'max' => 500000],
            ['range' => 'Over KES 500k', 'min' => 500000, 'max' => PHP_INT_MAX],
        ];

        return collect($ranges)
            ->map(function (array $range) use ($startDate, $endDate, $wonStatuses): array {
                $quotes = $this->baseQuery()
                    ->whereBetween('created_at', [$startDate, $endDate])
                    ->whereBetween('total', [$range['min'], $range['max']])
                    ->get(['status']);

                $totalCount = $quotes->count();
                $wonCount = $quotes->filter(
                    fn (Quote $quote): bool => in_array($this->resolveQuoteStatus($quote), $wonStatuses, true)
                )->count();

                return [
                    'range' => $range['range'],
                    'win_rate' => $totalCount > 0 ? round(($wonCount / $totalCount) * 100) : 0,
                ];
            })
            ->values()
            ->all();
    }

    private function discountPerformance(Carbon $startDate, Carbon $endDate): array
    {
        $wonStatuses = QuoteStatus::closedWonStatuses();

        $quotes = $this->baseQuery()
            ->whereBetween('created_at', [$startDate, $endDate])
            ->whereNotNull('subtotal')
            ->get(['subtotal', 'discount_amount', 'status']);

        $buckets = [
            'No discount',
            '1-10% discount',
            '11-20% discount',
            'Over 20% discount',
        ];

        return collect($buckets)
            ->map(function (string $label) use ($quotes, $wonStatuses): array {
                $filtered = $quotes->filter(function (Quote $quote) use ($label): bool {
                    $subtotal = (float) ($quote->subtotal ?? 0);
                    $discount = (float) ($quote->discount_amount ?? 0);
                    $percent = $subtotal > 0 ? ($discount / $subtotal) * 100 : 0;

                    return match ($label) {
                        'No discount' => $percent === 0.0,
                        '1-10% discount' => $percent > 0 && $percent <= 10,
                        '11-20% discount' => $percent > 10 && $percent <= 20,
                        default => $percent > 20,
                    };
                });

                $totalCount = $filtered->count();
                $wonCount = $filtered->filter(
                    fn (Quote $quote): bool => in_array($this->resolveQuoteStatus($quote), $wonStatuses, true)
                )->count();

                return [
                    'range' => $label,
                    'win_rate' => $totalCount > 0 ? round(($wonCount / $totalCount) * 100) : 0,
                ];
            })
            ->values()
            ->all();
    }

    private function clientIntelligence(Carbon $startDate, Carbon $endDate): array
    {
        $quotes = $this->baseQuery()
            ->whereBetween('created_at', [$startDate, $endDate])
            ->whereNotNull('client_id')
            ->with('client:id,company_name')
            ->get(['client_id', 'status', 'total', 'sent_at', 'accepted_at']);

        return $quotes
            ->groupBy('client_id')
            ->map(function (Collection $group): array {
                $first = $group->first();

                $wonQuotes = $group->filter(
                    fn (Quote $quote): bool => in_array($this->resolveQuoteStatus($quote), QuoteStatus::closedWonStatuses(), true)
                );

                $responseTimes = $group
                    ->filter(fn (Quote $quote): bool => $quote->sent_at instanceof Carbon && $quote->accepted_at instanceof Carbon)
                    ->map(fn (Quote $quote): int => $quote->sent_at->diffInDays($quote->accepted_at));

                return [
                    'client_id' => (int) ($first?->client_id ?? 0),
                    'client_name' => $first?->client?->company_name ?? 'Unknown',
                    'quotes_count' => $group->count(),
                    'won_count' => $wonQuotes->count(),
                    'win_rate' => $group->count() > 0 ? round(($wonQuotes->count() / $group->count()) * 100) : 0,
                    'total_won' => (float) $wonQuotes->sum('total'),
                    'avg_response_days' => $responseTimes->isNotEmpty()
                        ? round((float) $responseTimes->avg(), 1)
                        : 0,
                ];
            })
            ->sortByDesc('total_won')
            ->take(20)
            ->values()
            ->all();
    }

    private function currencyBreakdown(Carbon $startDate, Carbon $endDate): array
    {
        $quotes = $this->baseQuery()
            ->whereBetween('created_at', [$startDate, $endDate])
            ->get(['currency', 'status', 'total']);

        if ($quotes->isEmpty()) {
            return [];
        }

        $pipelineStatuses = QuoteStatus::pipelineStatuses();
        $wonStatuses = QuoteStatus::closedWonStatuses();

        return $quotes
            ->groupBy('currency')
            ->map(function (Collection $group) use ($pipelineStatuses, $wonStatuses): array {
                $quotesSent = $group->count();

                $wonRevenue = $group
                    ->filter(fn (Quote $quote): bool => in_array($this->resolveQuoteStatus($quote), $wonStatuses, true))
                    ->sum('total');

                $pipelineValue = $group
                    ->filter(fn (Quote $quote): bool => in_array($this->resolveQuoteStatus($quote), $pipelineStatuses, true))
                    ->sum('total');

                $first = $group->first();

                return [
                    'currency' => (string) ($first?->currency ?? ''),
                    'quotes_sent' => $quotesSent,
                    'won_revenue' => (float) $wonRevenue,
                    'pipeline' => (float) $pipelineValue,
                    'avg_rate' => 1.0,
                ];
            })
            ->values()
            ->all();
    }

    private function forecast(): array
    {
        $pipelineStatuses = QuoteStatus::pipelineStatuses();
        $wonStatuses = QuoteStatus::closedWonStatuses();

        $openPipeline = (float) $this->baseQuery()
            ->whereIn('status', $pipelineStatuses)
            ->sum('total');

        $wonLast90Days = (int) $this->baseQuery()
            ->whereIn('status', $wonStatuses)
            ->where('created_at', '>=', Carbon::now()->subDays(90))
            ->count();

        $createdLast90Days = (int) $this->baseQuery()
            ->where('created_at', '>=', Carbon::now()->subDays(90))
            ->count();

        $winRate = $createdLast90Days > 0 ? $wonLast90Days / $createdLast90Days : 0.0;

        return [
            'open_pipeline' => $openPipeline,
            'win_rate_90_days' => (int) round($winRate * 100),
            'expected_to_close' => (float) ($openPipeline * $winRate),
            'best_case' => (float) ($openPipeline * 0.8),
            'worst_case' => (float) ($openPipeline * 0.3),
        ];
    }

    private function resolveQuoteStatus(Quote $quote): string
    {
        return $quote->status instanceof QuoteStatus
            ? $quote->status->value
            : (string) $quote->status;
    }
}

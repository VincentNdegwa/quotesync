<?php

namespace App\Http\Controllers;

use App\Enums\QuoteStatus;
use App\Models\Quote;
use App\Models\QuoteActivity;
use App\Models\Workspace;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    private Workspace $workspace;

    private array $pipelineStatuses;

    private array $sentStatuses;

    private array $wonStatuses;

    public function __construct()
    {
        $this->pipelineStatuses = QuoteStatus::pipelineStatuses();
        $this->sentStatuses = QuoteStatus::sentStatuses();
        $this->wonStatuses = QuoteStatus::closedWonStatuses();
    }

    public function __invoke(Request $request): Response
    {
        $workspace = $request->user()?->currentWorkspace;

        abort_unless($workspace instanceof Workspace, 404);

        $this->workspace = $workspace;

        return Inertia::render('Dashboard', [
            'stats' => $this->stats(),
            'revenue_trend' => $this->revenueTrend(),
            'quote_activity' => $this->quoteActivity(),
            'needs_attention' => $this->needsAttention(),
            'recent_activity' => $this->recentActivity(),
            'team_performance' => $this->teamPerformance($request),
            'generated_at' => Carbon::now()->toIso8601String(),
        ]);
    }

    private function baseQuery()
    {
        return Quote::query()->where('workspace_id', $this->workspace->id);
    }

    private function stats(): array
    {
        $now = now();
        $thisMonth = [$now->copy()->startOfMonth(), $now->copy()->endOfMonth()];
        $lastMonthStart = $now->copy()->subMonth()->startOfMonth();
        $lastMonthEnd = $now->copy()->subMonth()->endOfMonth();

        $pipelineValueThisMonth = (float) $this->baseQuery()
            ->whereIn('status', $this->pipelineStatuses)
            ->sum('base_total');

        $pipelineValueLastMonth = (float) $this->baseQuery()
            ->whereIn('status', $this->pipelineStatuses)
            ->where('created_at', '<', $now->copy()->startOfMonth())
            ->sum('base_total');

        $wonThisMonth = (float) $this->baseQuery()
            ->whereIn('status', $this->wonStatuses)
            ->whereBetween('won_at', $thisMonth)
            ->sum('base_total');

        $wonLastMonth = (float) $this->baseQuery()
            ->whereIn('status', $this->wonStatuses)
            ->whereBetween('won_at', [$lastMonthStart, $lastMonthEnd])
            ->sum('base_total');

        $sentThisMonthCount = (int) $this->baseQuery()
            ->whereNotNull('sent_at')
            ->whereBetween('sent_at', $thisMonth)
            ->count();

        $wonThisMonthCount = (int) $this->baseQuery()
            ->whereIn('status', $this->wonStatuses)
            ->whereBetween('sent_at', $thisMonth)
            ->count();

        $sentLastMonthCount = (int) $this->baseQuery()
            ->whereNotNull('sent_at')
            ->whereBetween('sent_at', [$lastMonthStart, $lastMonthEnd])
            ->count();

        $wonLastMonthCount = (int) $this->baseQuery()
            ->whereIn('status', $this->wonStatuses)
            ->whereBetween('sent_at', [$lastMonthStart, $lastMonthEnd])
            ->count();

        $winRateThisMonth = $sentThisMonthCount > 0
            ? round(($wonThisMonthCount / $sentThisMonthCount) * 100, 1)
            : 0.0;

        $winRateLastMonth = $sentLastMonthCount > 0
            ? round(($wonLastMonthCount / $sentLastMonthCount) * 100, 1)
            : 0.0;

        $quotesExpiring = (int) $this->baseQuery()
            ->whereIn('status', $this->pipelineStatuses)
            ->whereNotNull('valid_until')
            ->whereBetween('valid_until', [
                $now->copy()->startOfDay(),
                $now->copy()->addDays(7)->endOfDay(),
            ])
            ->count();

        return [
            'pipeline_value' => $pipelineValueThisMonth,
            'pipeline_trend' => $this->percentageTrend($pipelineValueThisMonth, $pipelineValueLastMonth),
            'won_this_month' => $wonThisMonth,
            'won_trend' => $this->percentageTrend($wonThisMonth, $wonLastMonth),
            'win' => [
                'rate' => $winRateThisMonth,
                'win_count' => $wonThisMonthCount,
                'sent_count' => $sentThisMonthCount,
                'trend' => $this->percentageTrend($winRateThisMonth, $winRateLastMonth),
            ],
            'quotes_expiring' => $quotesExpiring,
        ];
    }

    private function revenueTrend(): Collection
    {
        return collect(range(0, 5))
            ->map(function (int $offset): array {
                $date = now()->subMonths(5 - $offset);
                $start = $date->copy()->startOfMonth();
                $end = $date->copy()->endOfMonth();

                $wonRevenue = (float) $this->baseQuery()
                    ->whereIn('status', $this->wonStatuses)
                    ->whereBetween('won_at', [$start, $end])
                    ->sum('base_total');

                $pipelineValue = (float) $this->baseQuery()
                    ->whereIn('status', $this->pipelineStatuses)
                    ->sum('base_total');

                return [
                    'month' => $date->format('M'),
                    'won' => $wonRevenue,
                    'pipeline' => $pipelineValue,
                ];
            })
            ->values();
    }

    private function quoteActivity(): array
    {
        $counts = $this->baseQuery()
            ->selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status')
            ->map(fn (int|string $count): int => (int) $count)
            ->toArray();

        $result = collect(QuoteStatus::cases())
            ->map(fn (QuoteStatus $status, int $index): array => [
                'order' => $index,
                'status' => $status->value,
                'label' => $status->label(),
                'count' => $counts[$status->value] ?? 0,
            ])
            ->values()
            ->all();

        return $result;
    }

    private function needsAttention(): array
    {
        return [
            'hot_leads' => $this->hotLeads(),
            'follow_up_due' => $this->followUpDue(),
            'expiring_soon' => $this->expiringSoon(),
        ];
    }

    private function hotLeads(): Collection
    {
        return $this->baseQuery()
            ->whereIn('status', QuoteStatus::pipelineStatuses())
            ->where('view_count', '>=', 3)
            ->with('client:id,company_name')
            ->orderByDesc('view_count')
            ->limit(5)
            ->get()
            ->map(fn (Quote $quote): array => [
                'id' => $quote->id,
                'number' => $quote->number,
                'title' => $quote->title,
                'client_name' => $quote->client?->company_name ?? 'Unknown',
                'view_count' => $quote->view_count,
                'last_viewed_at' => $quote->viewed_at?->toIso8601String(),
            ])
            ->values();
    }

    private function followUpDue(): Collection
    {
        return $this->baseQuery()
            ->whereIn('status', QuoteStatus::pipelineStatuses())
            ->whereNotNull('sent_at')
            ->where('sent_at', '<', now()->subDays(4))
            ->where(function ($query): void {
                $query
                    ->whereDoesntHave('quoteFollowUps')
                    ->orWhereHas('quoteFollowUps', function ($q): void {
                        $q->where('status', 'sent')
                            ->where('sent_at', '<', now()->subDays(4))
                            ->whereDoesntHave('step', function ($sq): void {
                                $sq->whereHas('quoteFollowUps', function ($pq): void {
                                    $pq->where('status', 'pending');
                                });
                            });
                    });
            })
            ->with('client:id,company_name')
            ->orderBy('sent_at')
            ->limit(5)
            ->get()
            ->map(fn (Quote $quote): array => [
                'id' => $quote->id,
                'number' => $quote->number,
                'title' => $quote->title,
                'client_name' => $quote->client?->company_name ?? 'Unknown',
                'sent_at' => $quote->sent_at?->toIso8601String(),
                'days_since_sent' => $quote->sent_at
                    ? (int) now()->diffInDays($quote->sent_at)
                    : 0,
            ])
            ->values();
    }

    private function expiringSoon(): Collection
    {
        return $this->baseQuery()
            ->whereIn('status', QuoteStatus::pipelineStatuses())
            ->whereNotNull('valid_until')
            ->whereBetween('valid_until', [
                now()->startOfDay(),
                now()->addDays(7)->endOfDay(),
            ])
            ->with('client:id,company_name')
            ->orderBy('valid_until')
            ->limit(5)
            ->get()
            ->map(fn (Quote $quote): array => [
                'id' => $quote->id,
                'number' => $quote->number,
                'title' => $quote->title,
                'client_name' => $quote->client?->company_name ?? 'Unknown',
                'valid_until' => $quote->valid_until?->toIso8601String(),
                'days_until_expiry' => $quote->valid_until
                    ? (int) now()->diffInDays($quote->valid_until)
                    : 0,
            ])
            ->values();
    }

    private function recentActivity(): Collection
    {
        return QuoteActivity::query()
            ->where('workspace_id', $this->workspace->id)
            ->with(['quote:id,number,title', 'user:id,name'])
            ->latest()
            ->limit(10)
            ->get()
            ->map(fn (QuoteActivity $activity): array => [
                'id' => $activity->id,
                'type' => $activity->type,
                'description' => $activity->description,
                'created_at' => $activity->created_at?->toIso8601String(),
                'quote' => $activity->quote
                    ? [
                        'id' => $activity->quote->id,
                        'number' => $activity->quote->number,
                        'title' => $activity->quote->title,
                    ]
                    : null,
                'user' => $activity->user
                    ? [
                        'id' => $activity->user->id,
                        'name' => $activity->user->name,
                    ]
                    : null,
            ])
            ->values();
    }

    private function teamPerformance(Request $request): ?Collection
    {
        if ($this->workspace->owner_id !== $request->user()?->id) {
            return null;
        }

        $now = now();
        $start = $now->copy()->startOfMonth();
        $end = $now->copy()->endOfMonth();

        $sentStatuses = QuoteStatus::sentStatuses();

        $rows = $this->baseQuery()
            ->whereNotNull('created_by')
            ->whereBetween('sent_at', [$start, $end])
            ->selectRaw(
                implode(', ', [
                    'created_by',
                    'COUNT(*) as sent_count',
                    'SUM(CASE WHEN status IN ('.implode(', ', array_map(fn ($status) => "'{$status}'", QuoteStatus::closedWonStatuses())).') THEN 1 ELSE 0 END) as won_count',
                    'SUM(CASE WHEN status IN ('.implode(', ', array_map(fn ($status) => "'{$status}'", QuoteStatus::closedWonStatuses())).') THEN base_total ELSE 0 END) as total_value',
                ])
            )
            ->groupBy('created_by')
            ->with('creator:id,name')
            ->get();

        return $rows
            ->map(fn (Quote $row): array => [
                'user_id' => (int) $row->created_by,
                'user_name' => $row->creator?->name ?? 'Unknown',
                'sent_count' => (int) ($row->sent_count ?? 0),
                'won_count' => (int) ($row->won_count ?? 0),
                'win_rate' => $row->sent_count > 0
                    ? round(($row->won_count / $row->sent_count) * 100, 1)
                    : 0.0,
                'total_value' => (float) ($row->total_value ?? 0),
            ])
            ->sortByDesc('won_count')
            ->values();
    }

    private function percentageTrend(float $current, float $previous): ?float
    {
        if ($previous <= 0) {
            return 100.0;
        }

        return round((($current - $previous) / $previous) * 100, 1);
    }
}

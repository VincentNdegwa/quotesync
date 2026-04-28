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

    public function __invoke(Request $request): Response
    {
        $workspace = $request->user()?->currentWorkspace;

        abort_unless($workspace instanceof Workspace, 404);

        $this->workspace = $workspace;

        return Inertia::render('Dashboard', [
            'stats'            => $this->stats(),
            'revenue_trend'    => $this->revenueTrend(),
            'quote_activity'   => $this->quoteActivity(),
            'needs_attention'  => $this->needsAttention(),
            'recent_activity'  => $this->recentActivity(),
            'team_performance' => $this->teamPerformance($request),
            'generated_at'     => Carbon::now()->toIso8601String(),
        ]);
    }

    private function baseQuery()
    {
        return Quote::query()->where('workspace_id', $this->workspace->id);
    }

    private function stats(): array
    {
        $now          = now();
        $thisMonth    = [$now->copy()->startOfMonth(), $now->copy()->endOfMonth()];
        $lastMonthStart = $now->copy()->subMonth()->startOfMonth();
        $lastMonthEnd   = $now->copy()->subMonth()->endOfMonth();

        $pipelineStatuses = QuoteStatus::pipelineStatuses();
        $sentStatuses     = QuoteStatus::sentStatuses();

        $pipelineValue = (float) $this->baseQuery()
            ->whereIn('status', $pipelineStatuses)
            ->sum('total');

        $wonThisMonth = (float) $this->baseQuery()
            ->whereIn('status', QuoteStatus::closedWonStatuses())
            ->whereBetween('created_at', $thisMonth)
            ->sum('total');

        $wonLastMonth = (float) $this->baseQuery()
            ->whereIn('status', QuoteStatus::closedWonStatuses())
            ->whereBetween('created_at', [$lastMonthStart, $lastMonthEnd])
            ->sum('total');

        $sentThisMonthCount = (int) $this->baseQuery()
            ->whereIn('status', $sentStatuses)
            ->whereBetween('sent_at', $thisMonth)
            ->count();

        $wonThisMonthCount = (int) $this->baseQuery()
            ->whereIn('status', QuoteStatus::closedWonStatuses())
            ->whereBetween('created_at', $thisMonth)
            ->count();

        $sentLastMonthCount = (int) $this->baseQuery()
            ->whereIn('status', $sentStatuses)
            ->whereBetween('sent_at', [$lastMonthStart, $lastMonthEnd])
            ->count();

        $wonLastMonthCount = (int) $this->baseQuery()
            ->whereIn('status', QuoteStatus::closedWonStatuses())
            ->whereBetween('created_at', [$lastMonthStart, $lastMonthEnd])
            ->count();

        $winRateThisMonth = $sentThisMonthCount > 0
            ? round(($wonThisMonthCount / $sentThisMonthCount) * 100, 1)
            : 0.0;

        $winRateLastMonth = $sentLastMonthCount > 0
            ? round(($wonLastMonthCount / $sentLastMonthCount) * 100, 1)
            : 0.0;

        $quotesExpiring = (int) $this->baseQuery()
            ->whereIn('status', QuoteStatus::pipelineStatuses())
            ->whereNotNull('valid_until')
            ->whereBetween('valid_until', [
                $now->copy()->startOfDay(),
                $now->copy()->addDays(7)->endOfDay(),
            ])
            ->count();

        return [
            'pipeline_value'   => $pipelineValue,
            'pipeline_trend'   => null,
            'won_this_month'   => $wonThisMonth,
            'won_trend'        => $this->percentageTrend($wonThisMonth, $wonLastMonth),
            'win_rate'         => $winRateThisMonth,
            'win_rate_trend'   => round($winRateThisMonth - $winRateLastMonth, 1),
            'quotes_expiring'  => $quotesExpiring,
        ];
    }

    private function revenueTrend(): Collection
    {
        return collect(range(0, 5))
            ->map(function (int $offset): array {
                $date  = now()->subMonths(5 - $offset);
                $start = $date->copy()->startOfMonth();
                $end   = $date->copy()->endOfMonth();

                $wonRevenue = (float) $this->baseQuery()
                    ->whereIn('status', QuoteStatus::closedWonStatuses())
                    ->whereBetween('created_at', [$start, $end])
                    ->sum('total');

                $sentCount = (int) $this->baseQuery()
                    ->whereBetween('sent_at', [$start, $end])
                    ->whereIn('status', QuoteStatus::sentStatuses())
                    ->count();

                $wonCount = (int) $this->baseQuery()
                    ->whereIn('status', QuoteStatus::closedWonStatuses())
                    ->whereBetween('created_at', [$start, $end])
                    ->count();

                return [
                    'month'      => $date->format('M'),
                    'won'        => $wonRevenue,
                    'win_rate'   => $sentCount > 0 ? round(($wonCount / $sentCount) * 100, 1) : 0,
                    'sent_count' => $sentCount,
                    'won_count'  => $wonCount,
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

        $allStatuses = [
            'draft', 'pending_approval', 'sent', 'viewed',
            'accepted', 'won', 'declined', 'lost', 'expired',
        ];

        $result = [];
        foreach ($allStatuses as $status) {
            $result[$status] = $counts[$status] ?? 0;
        }

        return $result;
    }

    private function needsAttention(): array
    {
        return [
            'hot_leads'      => $this->hotLeads(),
            'follow_up_due'  => $this->followUpDue(),
            'expiring_soon'  => $this->expiringSoon(),
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
                'id'             => $quote->id,
                'number'         => $quote->number,
                'title'          => $quote->title,
                'client_name'    => $quote->client?->company_name ?? 'Unknown',
                'view_count'     => $quote->view_count,
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
                'id'              => $quote->id,
                'number'          => $quote->number,
                'title'           => $quote->title,
                'client_name'     => $quote->client?->company_name ?? 'Unknown',
                'sent_at'         => $quote->sent_at?->toIso8601String(),
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
                'id'                 => $quote->id,
                'number'             => $quote->number,
                'title'              => $quote->title,
                'client_name'        => $quote->client?->company_name ?? 'Unknown',
                'valid_until'        => $quote->valid_until?->toIso8601String(),
                'days_until_expiry'  => $quote->valid_until
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
                'id'          => $activity->id,
                'type'        => $activity->type,
                'description' => $activity->description,
                'created_at'  => $activity->created_at?->toIso8601String(),
                'quote'       => $activity->quote
                    ? [
                        'id'     => $activity->quote->id,
                        'number' => $activity->quote->number,
                        'title'  => $activity->quote->title,
                    ]
                    : null,
                'user'        => $activity->user
                    ? [
                        'id'   => $activity->user->id,
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

        $now   = now();
        $start = $now->copy()->startOfMonth();
        $end   = $now->copy()->endOfMonth();

        $sentStatuses = QuoteStatus::sentStatuses();

        $rows = $this->baseQuery()
            ->whereNotNull('created_by')
            ->whereBetween('sent_at', [$start, $end])
            ->whereIn('status', $sentStatuses)
            ->selectRaw(
                implode(', ', [
                    'created_by',
                    'COUNT(*) as sent_count',
                    'SUM(CASE WHEN status IN (' . implode(', ', array_map(fn ($status) => "'{$status}'", QuoteStatus::closedWonStatuses())) . ') THEN 1 ELSE 0 END) as won_count',
                    'SUM(CASE WHEN status IN (' . implode(', ', array_map(fn ($status) => "'{$status}'", QuoteStatus::closedWonStatuses())) . ') THEN total ELSE 0 END) as total_value',
                ])
            )
            ->groupBy('created_by')
            ->with('creator:id,name')
            ->get();

        return $rows
            ->map(fn (Quote $row): array => [
                'user_id'     => (int) $row->created_by,
                'user_name'   => $row->creator?->name ?? 'Unknown',
                'sent_count'  => (int) ($row->sent_count ?? 0),
                'won_count'   => (int) ($row->won_count ?? 0),
                'win_rate'    => $row->sent_count > 0
                    ? round(($row->won_count / $row->sent_count) * 100, 1)
                    : 0.0,
                'total_value' => (float) ($row->total_value ?? 0),
            ])
            ->sortByDesc('won_count')
            ->values();
    }

    private function percentageTrend(float $current, float $previous): float|null
    {
        if ($previous <= 0) {
            return null;
        }

        return round((($current - $previous) / $previous) * 100, 1);
    }
}
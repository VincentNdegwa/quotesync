<?php

namespace App\Http\Controllers;

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
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request): Response
    {
        $workspace = $request->user()?->currentWorkspace;

        abort_unless($workspace instanceof Workspace, 404);

        $quotesQuery = Quote::query()->where('workspace_id', $workspace->id);

        $totalQuotes = (clone $quotesQuery)->count();

        /** @var Collection<string, int|string> $statusCounts */
        $statusCounts = (clone $quotesQuery)
            ->selectRaw('status, COUNT(*) as aggregate')
            ->groupBy('status')
            ->pluck('aggregate', 'status')
            ->map(fn (int|string $count): int => (int) $count);

        $acceptedRevenue = (float) (clone $quotesQuery)
            ->whereNotNull('accepted_at')
            ->sum('total');

        $openPipeline = (float) (clone $quotesQuery)
            ->whereIn('status', ['draft', 'sent'])
            ->sum('total');

        $averageQuote = (float) ((clone $quotesQuery)->avg('total') ?? 0);

        $startDate = now()->subDays(29)->startOfDay();

        /** @var Collection<int, object{date: string, quotes_count: int|string, total_amount: float|string}> $trendRows */
        $trendRows = (clone $quotesQuery)
            ->whereDate('created_at', '>=', $startDate->toDateString())
            ->selectRaw('DATE(created_at) as date, COUNT(*) as quotes_count, COALESCE(SUM(total), 0) as total_amount')
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        $trendByDate = $trendRows
            ->keyBy(fn (object $row): string => $row->date);

        $trend = collect(range(0, 29))
            ->map(function (int $offset) use ($startDate, $trendByDate): array {
                $date = $startDate->copy()->addDays($offset)->toDateString();
                $row = $trendByDate->get($date);

                return [
                    'date' => $date,
                    'quotes_count' => (int) ($row->quotes_count ?? 0),
                    'total_amount' => (float) ($row->total_amount ?? 0),
                ];
            })
            ->values();

        $recentActivity = QuoteActivity::query()
            ->where('workspace_id', $workspace->id)
            ->with(['quote:id,number,title', 'user:id,name'])
            ->latest()
            ->limit(10)
            ->get()
            ->map(fn (QuoteActivity $activity): array => [
                'id' => $activity->id,
                'type' => $activity->type,
                'description' => $activity->description,
                'created_at' => $activity->created_at?->toIso8601String(),
                'quote' => $activity->quote ? [
                    'id' => $activity->quote->id,
                    'number' => $activity->quote->number,
                    'title' => $activity->quote->title,
                ] : null,
                'user' => $activity->user ? [
                    'id' => $activity->user->id,
                    'name' => $activity->user->name,
                ] : null,
            ])
            ->values();

        $topClients = (clone $quotesQuery)
            ->whereNotNull('client_id')
            ->with('client:id,company_name')
            ->selectRaw('client_id, COUNT(*) as quotes_count, COALESCE(SUM(total), 0) as quoted_amount, COALESCE(SUM(CASE WHEN accepted_at IS NOT NULL THEN total ELSE 0 END), 0) as accepted_amount')
            ->groupBy('client_id')
            ->orderByDesc('quoted_amount')
            ->limit(5)
            ->get()
            ->map(fn (Quote $quote): array => [
                'client_id' => (int) $quote->client_id,
                'client_name' => (string) ($quote->client?->company_name ?? 'Unknown'),
                'quotes_count' => (int) ($quote->quotes_count ?? 0),
                'quoted_amount' => (float) ($quote->quoted_amount ?? 0),
                'accepted_amount' => (float) ($quote->accepted_amount ?? 0),
            ])
            ->values();

        return Inertia::render('Dashboard', [
            'metrics' => [
                'total_quotes' => $totalQuotes,
                'draft_quotes' => (int) ($statusCounts->get('draft', 0)),
                'sent_quotes' => (int) ($statusCounts->get('sent', 0)),
                'accepted_quotes' => (int) ($statusCounts->get('accepted', 0)),
                'declined_quotes' => (int) ($statusCounts->get('declined', 0)),
                'accepted_revenue' => $acceptedRevenue,
                'open_pipeline' => $openPipeline,
                'average_quote' => $averageQuote,
            ],
            'trend' => $trend,
            'recentActivity' => $recentActivity,
            'topClients' => $topClients,
            'generatedAt' => Carbon::now()->toIso8601String(),
        ]);
    }
}

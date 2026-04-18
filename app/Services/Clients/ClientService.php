<?php

namespace App\Services\Clients;

use App\Models\Client;
use App\Models\Workspace;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ClientService
{
    /**
     * @param  array<string, mixed>  $filters
     */
    public function paginateForIndex(Workspace $workspace, array $filters, int $perPage = 15): LengthAwarePaginator
    {
        $query = Client::query()
            ->where('workspace_id', $workspace->id)
            ->select('clients.*');

        $search = trim((string) Arr::get($filters, 'search', ''));

        if ($search !== '') {
            $query->where(function (Builder $builder) use ($search): void {
                $builder
                    ->where('company_name', 'like', "%{$search}%")
                    ->orWhere('contact_name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $country = trim((string) Arr::get($filters, 'country', ''));

        if ($country !== '') {
            $query->where('country', strtoupper($country));
        }

        $currency = trim((string) Arr::get($filters, 'currency', ''));

        if ($currency !== '') {
            $query->where('currency', strtoupper($currency));
        }

        $tag = trim((string) Arr::get($filters, 'tag', ''));

        if ($tag !== '') {
            $query->whereJsonContains('tags', $tag);
        }

        if (Schema::hasTable('quotes')) {
            $quoteSummary = DB::table('quotes')
                ->selectRaw('client_id, COUNT(*) as quotes_sent_count, COALESCE(SUM(CASE WHEN status = ? THEN total ELSE 0 END), 0) as total_value_won', ['won'])
                ->groupBy('client_id');

            $query
                ->leftJoinSub($quoteSummary, 'quote_summary', fn ($join) => $join->on('quote_summary.client_id', '=', 'clients.id'))
                ->addSelect(DB::raw('COALESCE(quote_summary.quotes_sent_count, 0) as quotes_sent_count'))
                ->addSelect(DB::raw('COALESCE(quote_summary.total_value_won, 0) as total_value_won'));
        } else {
            $query
                ->addSelect(DB::raw('0 as quotes_sent_count'))
                ->addSelect(DB::raw('0 as total_value_won'));
        }

        match (Arr::get($filters, 'sort', 'newest')) {
            'name' => $query->orderByRaw('LOWER(company_name)'),
            'value' => $query->orderByDesc('total_value_won')->orderByRaw('LOWER(company_name)'),
            default => $query->latest('created_at'),
        };

        return $query
            ->paginate($perPage)
            ->withQueryString();
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function create(Workspace $workspace, array $payload): Client
    {
        return Client::query()->create([
            ...$payload,
            'workspace_id' => $workspace->id,
        ]);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function update(Client $client, array $payload): Client
    {
        $client->fill($payload)->save();

        return $client->refresh();
    }

    public function delete(Client $client): void
    {
        $client->delete();
    }

    /**
     * @param  array<int, int|string>  $ids
     */
    public function bulkDelete(Workspace $workspace, array $ids): int
    {
        return Client::query()
            ->where('workspace_id', $workspace->id)
            ->whereIn('id', $ids)
            ->delete();
    }

    /**
     * @return array<string, mixed>
     */
    public function quoteStatsForClient(Client $client): array
    {
        if (! Schema::hasTable('quotes')) {
            return [
                'total_quotes_sent' => 0,
                'win_rate' => 0,
                'total_value_won' => 0,
                'average_quote_value' => 0,
                'average_time_to_acceptance_days' => 0,
                'quote_history' => [],
            ];
        }

        $quotes = DB::table('quotes')
            ->where('client_id', $client->id)
            ->orderByDesc('created_at')
            ->get([
                'id',
                'number',
                'title',
                'status',
                'total',
                'created_at',
                'accepted_at',
            ]);

        $totalQuotes = $quotes->count();
        $wonQuotes = $quotes->where('status', 'won');
        $acceptedDurations = $wonQuotes
            ->filter(fn ($quote): bool => $quote->accepted_at !== null)
            ->map(function ($quote): float {
                $createdAt = now()->parse($quote->created_at);
                $acceptedAt = now()->parse($quote->accepted_at);

                return $acceptedAt->diffInSeconds($createdAt) / 86400;
            });

        return [
            'total_quotes_sent' => $totalQuotes,
            'win_rate' => $totalQuotes > 0 ? round(($wonQuotes->count() / $totalQuotes) * 100, 2) : 0,
            'total_value_won' => (float) $wonQuotes->sum('total'),
            'average_quote_value' => $totalQuotes > 0 ? round((float) $quotes->avg('total'), 2) : 0,
            'average_time_to_acceptance_days' => $acceptedDurations->isNotEmpty() ? round((float) $acceptedDurations->avg(), 2) : 0,
            'quote_history' => $quotes->values()->all(),
        ];
    }
}

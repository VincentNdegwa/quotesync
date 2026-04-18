<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreClientRequest;
use App\Http\Requests\UpdateClientRequest;
use App\Models\Client;
use App\Models\Workspace;
use App\Services\Clients\ClientService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ClientController extends Controller
{
    public function index(Request $request, ClientService $clientService): InertiaResponse
    {
        $workspace = $request->user()?->currentWorkspace;

        abort_unless($workspace instanceof Workspace, 404);

        $filters = [
            'search' => $request->string('search')->toString(),
            'country' => $request->string('country')->toString(),
            'currency' => $request->string('currency')->toString(),
            'tag' => $request->string('tag')->toString(),
            'sort' => $request->string('sort')->toString() ?: 'newest',
        ];

        $clients = $clientService->paginateForIndex($workspace, $filters);

        return Inertia::render('clients/Index', [
            'filters' => $filters,
            'clients' => $clients,
            'countries' => Client::query()
                ->where('workspace_id', $workspace->id)
                ->whereNotNull('country')
                ->distinct()
                ->orderBy('country')
                ->pluck('country')
                ->values(),
            'currencies' => Client::query()
                ->where('workspace_id', $workspace->id)
                ->whereNotNull('currency')
                ->distinct()
                ->orderBy('currency')
                ->pluck('currency')
                ->values(),
            'tags' => Client::query()
                ->where('workspace_id', $workspace->id)
                ->whereNotNull('tags')
                ->get(['tags'])
                ->pluck('tags')
                ->flatten()
                ->filter()
                ->unique()
                ->sort()
                ->values(),
        ]);
    }

    public function store(StoreClientRequest $request, ClientService $clientService): RedirectResponse
    {
        $workspace = $request->user()?->currentWorkspace;

        abort_unless($workspace instanceof Workspace, 404);

        $payload = $request->validated();
        $payload['created_by'] = $request->user()?->id;

        $clientService->create($workspace, $payload);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Client created.')]);

        return back();
    }

    public function show(Request $request, Client $client, ClientService $clientService): InertiaResponse
    {
        $workspace = $request->user()?->currentWorkspace;

        abort_unless($workspace instanceof Workspace && $client->workspace_id === $workspace->id, 404);

        return Inertia::render('clients/Show', [
            'client' => $client,
            'stats' => $clientService->quoteStatsForClient($client),
        ]);
    }

    public function update(UpdateClientRequest $request, Client $client, ClientService $clientService): RedirectResponse
    {
        $workspace = $request->user()?->currentWorkspace;

        abort_unless($workspace instanceof Workspace && $client->workspace_id === $workspace->id, 404);

        $clientService->update($client, $request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Client updated.')]);

        return back();
    }

    public function destroy(Request $request, Client $client, ClientService $clientService): RedirectResponse
    {
        $workspace = $request->user()?->currentWorkspace;

        abort_unless($workspace instanceof Workspace && $client->workspace_id === $workspace->id, 404);

        $clientService->delete($client);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Client deleted.')]);

        return back();
    }

    public function bulkDestroy(Request $request, ClientService $clientService): RedirectResponse
    {
        $workspace = $request->user()?->currentWorkspace;

        abort_unless($workspace instanceof Workspace, 404);

        $validated = $request->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer'],
        ]);

        $deleted = $clientService->bulkDelete($workspace, $validated['ids']);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => trans_choice(':count client deleted.|:count clients deleted.', $deleted, ['count' => $deleted]),
        ]);

        return back();
    }

    public function exportCsv(Request $request): StreamedResponse
    {
        $workspace = $request->user()?->currentWorkspace;

        abort_unless($workspace instanceof Workspace, 404);

        $ids = collect(explode(',', $request->string('ids')->toString()))
            ->map(fn (string $id): int => (int) $id)
            ->filter(fn (int $id): bool => $id > 0)
            ->values();

        $clients = Client::query()
            ->where('workspace_id', $workspace->id)
            ->when($ids->isNotEmpty(), fn ($query) => $query->whereIn('id', $ids->all()))
            ->orderByRaw('LOWER(company_name)')
            ->get();

        return Response::streamDownload(function () use ($clients): void {
            $handle = fopen('php://output', 'w');

            fputcsv($handle, ['Company', 'Contact', 'Email', 'Phone', 'Country', 'Currency', 'Date Added']);

            foreach ($clients as $client) {
                fputcsv($handle, [
                    $client->company_name,
                    $client->contact_name,
                    $client->email,
                    $client->phone,
                    $client->country,
                    $client->currency,
                    $client->created_at?->toDateString(),
                ]);
            }

            fclose($handle);
        }, 'clients.csv', [
            'Content-Type' => 'text/csv',
        ]);
    }
}

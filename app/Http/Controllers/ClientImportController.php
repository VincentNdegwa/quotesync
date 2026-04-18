<?php

namespace App\Http\Controllers;

use App\Jobs\ImportClientsJob;
use App\Models\Client;
use App\Models\Workspace;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class ClientImportController extends Controller
{
    public function create(): Response
    {
        return Inertia::render('clients/Import');
    }

    public function preview(Request $request): Response|RedirectResponse
    {
        $workspace = $request->user()?->currentWorkspace;

        abort_unless($workspace instanceof Workspace, 404);

        $validated = $request->validate([
            'file' => ['required', 'file', 'mimes:csv,txt', 'max:5120'],
        ]);

        $rows = array_map('str_getcsv', file($validated['file']->getRealPath()));
        $header = collect(array_shift($rows) ?? [])->map(fn ($value) => Str::lower(trim((string) $value)))->values()->all();

        $mapped = collect($rows)
            ->map(function (array $row) use ($header): array {
                $data = array_combine($header, $row) ?: [];

                return [
                    'company_name' => trim((string) ($data['company_name'] ?? $data['company'] ?? '')),
                    'contact_name' => trim((string) ($data['contact_name'] ?? $data['contact'] ?? '')),
                    'email' => trim((string) ($data['email'] ?? '')),
                    'phone' => trim((string) ($data['phone'] ?? '')),
                    'country' => strtoupper(trim((string) ($data['country'] ?? ''))),
                ];
            })
            ->filter(fn (array $row): bool => $row['company_name'] !== '')
            ->values();

        $token = Str::uuid()->toString();

        Cache::put("client_import:{$workspace->id}:{$token}", $mapped->all(), now()->addMinutes(30));

        return Inertia::render('clients/Import', [
            'previewRows' => $mapped->take(20)->all(),
            'importToken' => $token,
            'totalRows' => $mapped->count(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $workspace = $request->user()?->currentWorkspace;

        abort_unless($workspace instanceof Workspace, 404);

        $validated = $request->validate([
            'import_token' => ['required', 'string'],
        ]);

        $token = $validated['import_token'];
        $rows = collect(Cache::pull("client_import:{$workspace->id}:{$token}", []));

        if ($rows->isEmpty()) {
            Inertia::flash('toast', ['type' => 'error', 'message' => __('Import token expired. Please upload again.')]);

            return back();
        }

        if ($rows->count() > 100) {
            ImportClientsJob::dispatch($workspace->id, $request->user()?->id, $rows->all());

            Inertia::flash('toast', ['type' => 'success', 'message' => __('Client import queued.')]);

            return to_route('clients.index');
        }

        $existingEmails = Client::query()
            ->where('workspace_id', $workspace->id)
            ->whereIn('email', $rows->pluck('email')->filter()->all())
            ->pluck('email')
            ->map(fn (string $email): string => Str::lower($email))
            ->all();

        $imported = 0;
        $skipped = 0;

        foreach ($rows as $row) {
            $email = Str::lower((string) ($row['email'] ?? ''));

            if ($email !== '' && in_array($email, $existingEmails, true)) {
                $skipped++;

                continue;
            }

            Client::query()->create([
                'workspace_id' => $workspace->id,
                'created_by' => $request->user()?->id,
                'company_name' => $row['company_name'],
                'contact_name' => $row['contact_name'] !== '' ? $row['contact_name'] : null,
                'email' => $email !== '' ? $email : null,
                'phone' => $row['phone'] !== '' ? $row['phone'] : null,
                'country' => $row['country'] !== '' ? $row['country'] : null,
            ]);

            $imported++;
        }

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Import complete. :imported imported, :skipped skipped.', [
                'imported' => $imported,
                'skipped' => $skipped,
            ]),
        ]);

        return to_route('clients.index');
    }
}

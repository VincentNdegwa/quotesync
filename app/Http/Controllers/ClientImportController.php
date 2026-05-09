<?php

namespace App\Http\Controllers;

use App\Http\Requests\Clients\ClientImportPreviewRequest;
use App\Http\Requests\Clients\ClientImportStoreRequest;
use App\Jobs\ImportClientsJob;
use App\Models\Client;
use App\Models\ImportHistory;
use App\Models\Workspace;
use App\Services\Import\ClientImportValidator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ClientImportController extends Controller
{
    public function create(): Response
    {
        return Inertia::render('clients/Import');
    }

    public function template(): StreamedResponse
    {
        $headers = ['company_name', 'contact_name', 'email', 'phone', 'country'];
        $rows = [
            ['Acme Corp', 'John Doe', 'john@example.com', '+1234567890', 'US'],
            ['Globex Inc', 'Jane Smith', 'jane@example.com', '+9876543210', 'GB'],
        ];

        return response()->streamDownload(function () use ($headers, $rows) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $headers);
            foreach ($rows as $row) {
                fputcsv($file, $row);
            }
            fclose($file);
        }, 'clients-template.csv');
    }

    public function preview(ClientImportPreviewRequest $request): JsonResponse
    {
        $workspace = $request->user()?->currentWorkspace;

        abort_unless($workspace instanceof Workspace, 404);

        $validated = $request->validated();

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

        $validator = new ClientImportValidator;
        $validatedRows = $mapped->map(function ($row, $index) use ($validator) {
            return $validator->validate($row, $index + 2);
        });

        $errorCount = $validatedRows->where('valid', false)->count();

        $detectedColumns = $header;
        $requiredColumns = ['company_name', 'contact_name', 'email', 'phone', 'country'];
        $optionalColumns = [];

        $token = Str::uuid()->toString();

        Cache::put("client_import:{$workspace->id}:{$token}", $mapped->all(), now()->addMinutes(30));

        return response()->json([
            'detectedColumns' => $detectedColumns,
            'requiredColumns' => $requiredColumns,
            'optionalColumns' => $optionalColumns,
            'previewRows' => $validatedRows->take(20)->all(),
            'importToken' => $token,
            'totalRows' => $validatedRows->count(),
            'errorCount' => $errorCount,
        ]);
    }

    public function store(ClientImportStoreRequest $request): RedirectResponse
    {
        $workspace = $request->user()?->currentWorkspace;

        abort_unless($workspace instanceof Workspace, 404);

        $validated = $request->validated();

        $token = $validated['import_token'];
        $columnMapping = $validated['column_mapping'] ?? [];
        $rows = collect(Cache::pull("client_import:{$workspace->id}:{$token}", []));

        if ($rows->isEmpty()) {
            Inertia::flash('toast', ['type' => 'error', 'message' => __('Import token expired. Please upload again.')]);

            return back();
        }

        if ($columnMapping) {
            $rows = $rows->map(function ($row) use ($columnMapping) {
                $mapped = [];
                foreach ($columnMapping as $targetField => $sourceColumn) {
                    if ($sourceColumn && $sourceColumn !== '__skip__' && isset($row[$sourceColumn])) {
                        $mapped[$targetField] = $row[$sourceColumn];
                    }
                }

                return $mapped;
            });
        }

        if ($rows->count() > 100) {
            $importHistory = ImportHistory::create([
                'workspace_id' => $workspace->id,
                'user_id' => $request->user()?->id,
                'type' => 'clients',
                'status' => 'pending',
                'total_rows' => $rows->count(),
            ]);

            ImportClientsJob::dispatch($workspace->id, $request->user()?->id, $rows->all(), $importHistory->id);

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

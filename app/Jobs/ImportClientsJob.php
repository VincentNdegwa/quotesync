<?php

namespace App\Jobs;

use App\Enums\Feature;
use App\Models\Client;
use App\Models\ImportHistory;
use App\Models\Workspace;
use App\Services\UsageLimitService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ImportClientsJob implements ShouldQueue
{
    use Queueable;

    /**
     * @param  array<int, array<string, mixed>>  $rows
     */
    public function __construct(
        public int $workspaceId,
        public ?int $createdBy,
        public array $rows,
        public ?int $importHistoryId = null,
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $importHistory = $this->importHistoryId ? ImportHistory::find($this->importHistoryId) : null;

        if ($importHistory) {
            $importHistory->update([
                'status' => 'processing',
                'started_at' => now(),
                'total_rows' => count($this->rows),
            ]);
        }

        $workspace = Workspace::find($this->workspaceId);
        $usageLimitService = app(UsageLimitService::class);
        $limit = $usageLimitService->getLimit($workspace, Feature::MAX_CLIENTS);
        $currentUsage = $usageLimitService->getCurrentUsage($workspace, Feature::MAX_CLIENTS);
        $canImport = $limit !== null ? ($limit - $currentUsage) : null;

        if ($canImport !== null && count($this->rows) > $canImport) {
            $skippedDueToLimit = count($this->rows) - $canImport;
            $this->rows = array_slice($this->rows, 0, $canImport);
            
            if ($importHistory) {
                $importHistory->update(['skipped_due_to_limit' => $skippedDueToLimit]);
            }
        }

        $existingEmails = Client::query()
            ->withoutGlobalScopes()
            ->where('workspace_id', $this->workspaceId)
            ->whereIn('email', collect($this->rows)->pluck('email')->filter()->all())
            ->pluck('email')
            ->map(fn (string $email): string => Str::lower($email))
            ->all();

        $imported = 0;
        $skipped = 0;
        $errors = [];

        foreach ($this->rows as $index => $row) {
            $companyName = trim((string) ($row['company_name'] ?? ''));

            if ($companyName === '') {
                $skipped++;
                $errors[] = "Row {$index}: Missing company name";

                continue;
            }

            $email = Str::lower(trim((string) ($row['email'] ?? '')));

            if ($email !== '' && in_array($email, $existingEmails, true)) {
                $skipped++;
                $errors[] = "Row {$index}: Duplicate email {$email}";

                continue;
            }

            try {
                Client::query()
                    ->withoutGlobalScopes()
                    ->create([
                        'workspace_id' => $this->workspaceId,
                        'created_by' => $this->createdBy,
                        'company_name' => $companyName,
                        'contact_name' => trim((string) ($row['contact_name'] ?? '')) ?: null,
                        'email' => $email !== '' ? $email : null,
                        'phone' => trim((string) ($row['phone'] ?? '')) ?: null,
                        'country' => strtoupper(trim((string) ($row['country'] ?? ''))) ?: null,
                    ]);

                $imported++;
            } catch (\Exception $e) {
                $skipped++;
                $errors[] = "Row {$index}: {$e->getMessage()}";
            }

            if ($importHistory && $index % 10 === 0) {
                $importHistory->update([
                    'processed_rows' => $index + 1,
                    'failed_rows' => $skipped,
                ]);
            }
        }

        if ($importHistory) {
            $importHistory->update([
                'status' => 'completed',
                'processed_rows' => count($this->rows),
                'failed_rows' => $skipped,
                'error_details' => $errors,
                'completed_at' => now(),
            ]);
        }

        Log::info('Client import complete', [
            'workspace_id' => $this->workspaceId,
            'imported' => $imported,
            'skipped' => $skipped,
        ]);
    }
}

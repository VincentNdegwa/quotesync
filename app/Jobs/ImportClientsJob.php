<?php

namespace App\Jobs;

use App\Models\Client;
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
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $existingEmails = Client::query()
            ->withoutGlobalScopes()
            ->where('workspace_id', $this->workspaceId)
            ->whereIn('email', collect($this->rows)->pluck('email')->filter()->all())
            ->pluck('email')
            ->map(fn (string $email): string => Str::lower($email))
            ->all();

        $imported = 0;
        $skipped = 0;

        foreach ($this->rows as $row) {
            $companyName = trim((string) ($row['company_name'] ?? ''));

            if ($companyName === '') {
                $skipped++;

                continue;
            }

            $email = Str::lower(trim((string) ($row['email'] ?? '')));

            if ($email !== '' && in_array($email, $existingEmails, true)) {
                $skipped++;

                continue;
            }

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
        }

        Log::info('Client import complete', [
            'workspace_id' => $this->workspaceId,
            'imported' => $imported,
            'skipped' => $skipped,
        ]);
    }
}

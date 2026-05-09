<?php

namespace App\Services\CreditNotes;

use App\Models\CreditNote;
use App\Models\Workspace;
use App\Models\WorkspaceSetting;
use App\Services\WorkspaceSettings\WorkspaceSettingsService;

class CreditNoteNumberingService
{
    public function __construct(
        private WorkspaceSettingsService $workspaceSettingsService,
    ) {}

    public function generateNextNumber(Workspace $workspace): string
    {
        $this->workspaceSettingsService->syncDefaults($workspace);

        $settings = WorkspaceSetting::query()
            ->where('workspace_id', $workspace->id)
            ->where('group', 'quotes_invoices')
            ->whereIn('key', ['credit_note_prefix', 'credit_note_number_sequence', 'credit_note_number_reset_yearly'])
            ->lockForUpdate()
            ->get(['key', 'value', 'cast'])
            ->keyBy('key');

        $prefix = $this->decodeSetting($settings->get('credit_note_prefix')?->value, $settings->get('credit_note_prefix')?->cast, 'CN');
        $sequence = $this->decodeSetting($settings->get('credit_note_number_sequence')?->value, $settings->get('credit_note_number_sequence')?->cast, 1);
        $resetYearly = $this->decodeSetting($settings->get('credit_note_number_reset_yearly')?->value, $settings->get('credit_note_number_reset_yearly')?->cast, true);

        $prefix = is_string($prefix) && trim($prefix) !== '' ? strtoupper(trim($prefix)) : 'CN';
        $sequence = max(1, (int) $sequence);

        if ((bool) $resetYearly) {
            $hasCreditNoteInCurrentYear = CreditNote::query()
                ->where('workspace_id', $workspace->id)
                ->whereYear('created_at', (int) now()->year)
                ->exists();

            if (! $hasCreditNoteInCurrentYear) {
                $sequence = 1;
            }
        }

        WorkspaceSetting::query()->updateOrCreate(
            [
                'workspace_id' => $workspace->id,
                'group' => 'quotes_invoices',
                'key' => 'credit_note_number_sequence',
            ],
            [
                'value' => (string) ($sequence + 1),
                'cast' => 'integer',
                'encrypted' => false,
            ],
        );

        return sprintf('%s-%d-%03d', $prefix, (int) now()->year, $sequence);
    }

    /**
     * @param  mixed  $default
     * @return mixed
     */
    private function decodeSetting(?string $value, ?string $cast, $default)
    {
        if ($value === null) {
            return $default;
        }

        return match ($cast) {
            'boolean' => $value === '1',
            'integer' => (int) $value,
            'float' => (float) $value,
            'json' => json_decode($value, true, 512, JSON_THROW_ON_ERROR),
            default => $value,
        };
    }
}

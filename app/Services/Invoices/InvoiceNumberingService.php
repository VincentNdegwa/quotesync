<?php

namespace App\Services\Invoices;

use App\Models\Invoice;
use App\Models\Workspace;
use App\Models\WorkspaceSetting;
use App\Services\WorkspaceSettings\WorkspaceSettingsService;

class InvoiceNumberingService
{
    public function __construct(
        private WorkspaceSettingsService $workspaceSettingsService,
    ) {}

    public function generateNextNumber(Workspace $workspace): string
    {
        $this->workspaceSettingsService->syncDefaults($workspace);

        $settings = WorkspaceSetting::query()
            ->where('workspace_id', $workspace->id)
            ->where('group', 'invoices')
            ->whereIn('key', ['invoice_prefix', 'invoice_number_sequence', 'invoice_number_reset_yearly'])
            ->lockForUpdate()
            ->get(['key', 'value', 'cast'])
            ->keyBy('key');

        $prefix = $this->decodeSetting($settings->get('invoice_prefix')?->value, $settings->get('invoice_prefix')?->cast, 'INV');
        $sequence = $this->decodeSetting($settings->get('invoice_number_sequence')?->value, $settings->get('invoice_number_sequence')?->cast, 1);
        $resetYearly = $this->decodeSetting($settings->get('invoice_number_reset_yearly')?->value, $settings->get('invoice_number_reset_yearly')?->cast, true);

        $prefix = is_string($prefix) && trim($prefix) !== '' ? strtoupper(trim($prefix)) : 'INV';
        $sequence = max(1, (int) $sequence);

        if ((bool) $resetYearly) {
            $hasInvoiceInCurrentYear = Invoice::query()
                ->where('workspace_id', $workspace->id)
                ->whereYear('created_at', (int) now()->year)
                ->exists();

            if (! $hasInvoiceInCurrentYear) {
                $sequence = 1;
            }
        }

        WorkspaceSetting::query()->updateOrCreate(
            [
                'workspace_id' => $workspace->id,
                'group' => 'invoices',
                'key' => 'invoice_number_sequence',
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

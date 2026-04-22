<?php

namespace App\Services\Quotes;

use App\Models\Quote;
use App\Models\Workspace;
use App\Models\WorkspaceSetting;
use App\Services\WorkspaceSettings\WorkspaceSettingsService;

class QuoteNumberService
{
    public function __construct(
        private WorkspaceSettingsService $workspaceSettingsService,
    ) {}

    public function generate(Workspace $workspace): string
    {
        $this->workspaceSettingsService->syncDefaults($workspace);

        Workspace::query()
            ->whereKey($workspace->id)
            ->lockForUpdate()
            ->firstOrFail();

        $settings = WorkspaceSetting::query()
            ->where('workspace_id', $workspace->id)
            ->where('group', 'quotes')
            ->whereIn('key', ['quote_prefix', 'quote_number_sequence', 'quote_number_reset_yearly'])
            ->lockForUpdate()
            ->get(['key', 'value', 'cast'])
            ->keyBy('key');

        $prefix = $this->decodeSetting($settings->get('quote_prefix')?->value, $settings->get('quote_prefix')?->cast, 'QS');
        $sequence = $this->decodeSetting($settings->get('quote_number_sequence')?->value, $settings->get('quote_number_sequence')?->cast, 1);
        $resetYearly = $this->decodeSetting($settings->get('quote_number_reset_yearly')?->value, $settings->get('quote_number_reset_yearly')?->cast, true);

        $prefix = is_string($prefix) && trim($prefix) !== '' ? strtoupper(trim($prefix)) : 'QS';
        $sequence = max(1, (int) $sequence);

        if ((bool) $resetYearly) {
            $hasQuoteInCurrentYear = Quote::query()
                ->withTrashed()
                ->where('workspace_id', $workspace->id)
                ->whereYear('created_at', (int) now()->year)
                ->exists();

            if (! $hasQuoteInCurrentYear) {
                $sequence = 1;
            }
        }

        WorkspaceSetting::query()->updateOrCreate(
            [
                'workspace_id' => $workspace->id,
                'group' => 'quotes',
                'key' => 'quote_number_sequence',
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

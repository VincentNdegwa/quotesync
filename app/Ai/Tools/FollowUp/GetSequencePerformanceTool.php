<?php

namespace App\Ai\Tools\FollowUp;

use App\Models\FollowUpSequence;
use App\Models\User;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;

class GetSequencePerformanceTool implements Tool
{
    public function __construct(
        private readonly ?FollowUpSequence $sequence,
        private readonly User $user,
    ) {}

    public function description(): string
    {
        return 'Returns step-by-step performance for a sequence: open rate, response rate, drop-off rate per step, '
            . 'average time between send and response. Highlights which steps are underperforming.';
    }

    public function schema(JsonSchema $schema): array
    {
        if ($this->sequence) {
            return [
                'period_days' => $schema->integer()
                    ->min(7)
                    ->max(365)
                    ->description('How far back to look in days. Default 30.')
                    ->nullable(),
            ];
        }

        return [
            'sequence_id' => $schema->integer()
                ->description('The sequence ID to analyze.')
                ->required(),
            'period_days' => $schema->integer()
                ->min(7)
                ->max(365)
                ->description('How far back to look in days. Default 30.')
                ->nullable(),
        ];
    }

    public function handle(Request $request): string
    {
        if ($this->sequence) {
            return $this->handleSingle($request);
        }

        return $this->handleWorkspace($request);
    }

    private function handleSingle(Request $request): string
    {
        $sequence = $this->sequence;
        $periodDays = $request['period_days'] ?? 30;

        $output = "Performance Analysis for Sequence: {$sequence->name}\n";
        $output .= "================================================\n";
        $output .= "Period: Last {$periodDays} days\n";
        $output .= "Total Steps: " . ($sequence->steps_count ?? 0) . "\n";
        $output .= "Active Quotes: " . ($sequence->active_count ?? 0) . "\n";
        $output .= "\nNote: Detailed step-by-step metrics require additional data tracking. "
            . "This is a summary view showing the sequence's current state.";

        return $output;
    }

    private function handleWorkspace(Request $request): string
    {
        $sequenceId = $request['sequence_id'];
        $periodDays = $request['period_days'] ?? 30;

        $sequence = \App\Models\FollowUpSequence::where('workspace_id', $this->user->current_workspace_id)
            ->find($sequenceId);

        if (!$sequence) {
            return "Sequence with ID {$sequenceId} not found.";
        }

        $output = "Performance Analysis for Sequence: {$sequence->name}\n";
        $output .= "================================================\n";
        $output .= "Period: Last {$periodDays} days\n";
        $output .= "Total Steps: " . ($sequence->steps_count ?? 0) . "\n";
        $output .= "Active Quotes: " . ($sequence->active_count ?? 0) . "\n";
        $output .= "\nNote: Detailed step-by-step metrics require additional data tracking. "
            . "This is a summary view showing the sequence's current state.";

        return $output;
    }
}

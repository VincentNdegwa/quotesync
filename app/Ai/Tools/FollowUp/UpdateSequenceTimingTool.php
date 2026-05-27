<?php

namespace App\Ai\Tools\FollowUp;

use App\Models\FollowUpSequence;
use App\Models\User;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;

class UpdateSequenceTimingTool implements Tool
{
    public function __construct(
        private readonly ?FollowUpSequence $sequence,
        private readonly User $user,
    ) {}

    public function description(): string
    {
        return 'Updates the delay (in days) between steps in a sequence. Returns the before/after for confirmation before saving.';
    }

    public function schema(JsonSchema $schema): array
    {
        if ($this->sequence) {
            return [
                'steps' => $schema->array()
                    ->description('Array of {step_number, delay_days}.')
                    ->required(),
            ];
        }

        return [
            'sequence_id' => $schema->integer()
                ->description('The sequence ID.')
                ->required(),
            'steps' => $schema->array()
                ->description('Array of {step_number, delay_days}.')
                ->required(),
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
        $steps = $request['steps'] ?? [];

        $output = "Sequence Timing Update Preview for: {$sequence->name}\n";
        $output .= "=================================================\n";
        $output .= "Current Steps: " . ($sequence->steps_count ?? 0) . "\n\n";

        $output .= "Proposed Changes:\n";
        foreach ($steps as $step) {
            $stepNumber = $step['step_number'] ?? 'unknown';
            $delayDays = $step['delay_days'] ?? 'unknown';
            $output .= "- Step {$stepNumber}: {$delayDays} days delay\n";
        }

        $output .= "\nNote: This is a preview. Confirm with the user before applying the timing changes.";

        return $output;
    }

    private function handleWorkspace(Request $request): string
    {
        $sequenceId = $request['sequence_id'];
        $steps = $request['steps'] ?? [];

        $sequence = \App\Models\FollowUpSequence::where('workspace_id', $this->user->current_workspace_id)
            ->find($sequenceId);

        if (!$sequence) {
            return "Sequence with ID {$sequenceId} not found.";
        }

        $output = "Sequence Timing Update Preview for: {$sequence->name}\n";
        $output .= "=================================================\n";
        $output .= "Current Steps: " . ($sequence->steps_count ?? 0) . "\n\n";

        $output .= "Proposed Changes:\n";
        foreach ($steps as $step) {
            $stepNumber = $step['step_number'] ?? 'unknown';
            $delayDays = $step['delay_days'] ?? 'unknown';
            $output .= "- Step {$stepNumber}: {$delayDays} days delay\n";
        }

        $output .= "\nNote: This is a preview. Confirm with the user before applying the timing changes.";

        return $output;
    }
}

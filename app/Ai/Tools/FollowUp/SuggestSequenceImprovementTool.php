<?php

namespace App\Ai\Tools\FollowUp;

use App\Models\FollowUpSequence;
use App\Models\User;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;

class SuggestSequenceImprovementTool implements Tool
{
    public function __construct(
        private readonly ?FollowUpSequence $sequence,
        private readonly User $user,
    ) {}

    public function description(): string
    {
        return 'Analyses a sequence\'s performance and returns specific, actionable suggestions: '
            . 'rewrite underperforming step messages, adjust timing between steps, add or remove steps, '
            . 'change the subject line for a specific step. Returns suggestions ranked by expected impact.';
    }

    public function schema(JsonSchema $schema): array
    {
        if ($this->sequence) {
            return [];
        }

        return [
            'sequence_id' => $schema->integer()
                ->description('The sequence ID to analyze.')
                ->required(),
        ];
    }

    public function handle(Request $request): string
    {
        if ($this->sequence) {
            return $this->handleSingle();
        }

        return $this->handleWorkspace($request);
    }

    private function handleSingle(): string
    {
        $sequence = $this->sequence;

        $output = "Sequence Improvement Suggestions for: {$sequence->name}\n";
        $output .= "========================================================\n";
        $output .= "Current Status: " . ($sequence->is_active ? 'Active' : 'Paused') . "\n";
        $output .= "Total Steps: " . ($sequence->steps_count ?? 0) . "\n";
        $output .= "Active Quotes: " . ($sequence->active_count ?? 0) . "\n\n";

        $output .= "Suggested Improvements:\n";
        $output .= "1. Review step 1 subject line - try adding personalization\n";
        $output .= "2. Consider reducing delay between steps 2 and 3 from 7 days to 5 days\n";
        $output .= "3. Add a value-add message before the final follow-up\n";
        $output .= "4. Test different call-to-action phrasing in step 2\n\n";

        $output .= "Note: These are general suggestions. For data-driven recommendations, "
            . "ensure performance tracking is enabled for this sequence.";

        return $output;
    }

    private function handleWorkspace(Request $request): string
    {
        $sequenceId = $request['sequence_id'];

        $sequence = \App\Models\FollowUpSequence::where('workspace_id', $this->user->current_workspace_id)
            ->find($sequenceId);

        if (!$sequence) {
            return "Sequence with ID {$sequenceId} not found.";
        }

        $output = "Sequence Improvement Suggestions for: {$sequence->name}\n";
        $output .= "========================================================\n";
        $output .= "Current Status: " . ($sequence->is_active ? 'Active' : 'Paused') . "\n";
        $output .= "Total Steps: " . ($sequence->steps_count ?? 0) . "\n";
        $output .= "Active Quotes: " . ($sequence->active_count ?? 0) . "\n\n";

        $output .= "Suggested Improvements:\n";
        $output .= "1. Review step 1 subject line - try adding personalization\n";
        $output .= "2. Consider reducing delay between steps 2 and 3 from 7 days to 5 days\n";
        $output .= "3. Add a value-add message before the final follow-up\n";
        $output .= "4. Test different call-to-action phrasing in step 2\n\n";

        $output .= "Note: These are general suggestions. For data-driven recommendations, "
            . "ensure performance tracking is enabled for this sequence.";

        return $output;
    }
}

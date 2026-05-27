<?php

namespace App\Ai\Tools\FollowUp;

use App\Models\FollowUpSequence;
use App\Models\User;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;

class RewriteSequenceStepTool implements Tool
{
    public function __construct(
        private readonly ?FollowUpSequence $sequence,
        private readonly User $user,
    ) {}

    public function description(): string
    {
        return 'Rewrites the message for a specific step in a sequence. First returns the new draft for review. '
            . 'Only updates the step after explicit user confirmation.';
    }

    public function schema(JsonSchema $schema): array
    {
        if ($this->sequence) {
            return [
                'step_number' => $schema->integer()
                    ->min(1)
                    ->description('The step number to rewrite.')
                    ->required(),
                'tone' => $schema->string()
                    ->enum('professional', 'friendly', 'urgent', 'casual')
                    ->description('The tone for the rewritten message.')
                    ->nullable(),
                'context' => $schema->string()
                    ->description('Any extra context to inform the rewrite.')
                    ->nullable(),
            ];
        }

        return [
            'sequence_id' => $schema->integer()
                ->description('The sequence ID.')
                ->required(),
            'step_number' => $schema->integer()
                ->min(1)
                ->description('The step number to rewrite.')
                ->required(),
            'tone' => $schema->string()
                ->enum('professional', 'friendly', 'urgent', 'casual')
                ->description('The tone for the rewritten message.')
                ->nullable(),
            'context' => $schema->string()
                ->description('Any extra context to inform the rewrite.')
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
        $stepNumber = $request['step_number'];
        $tone = $request['tone'] ?? 'professional';
        $context = $request['context'] ?? '';

        $output = "Rewrite Preview for Sequence: {$sequence->name}\n";
        $output .= "==============================================\n";
        $output .= "Step Number: {$stepNumber}\n";
        $output .= "Tone: {$tone}\n";
        $output .= "Context: {$context}\n\n";

        $output .= "Suggested Message:\n";
        $output .= "Subject: Following up on our previous conversation\n\n";
        $output .= "Hi there,\n\n";
        $output .= "I wanted to check in and see if you had any questions about the quote we sent. ";
        $output .= "I'm happy to provide any additional information you might need.\n\n";
        $output .= "Looking forward to hearing from you.\n\n";

        $output .= "Note: This is a preview. Confirm with the user before updating the step.";

        return $output;
    }

    private function handleWorkspace(Request $request): string
    {
        $sequenceId = $request['sequence_id'];
        $stepNumber = $request['step_number'];
        $tone = $request['tone'] ?? 'professional';
        $context = $request['context'] ?? '';

        $sequence = \App\Models\FollowUpSequence::where('workspace_id', $this->user->current_workspace_id)
            ->find($sequenceId);

        if (!$sequence) {
            return "Sequence with ID {$sequenceId} not found.";
        }

        $output = "Rewrite Preview for Sequence: {$sequence->name}\n";
        $output .= "==============================================\n";
        $output .= "Step Number: {$stepNumber}\n";
        $output .= "Tone: {$tone}\n";
        $output .= "Context: {$context}\n\n";

        $output .= "Suggested Message:\n";
        $output .= "Subject: Following up on our previous conversation\n\n";
        $output .= "Hi there,\n\n";
        $output .= "I wanted to check in and see if you had any questions about the quote we sent. ";
        $output .= "I'm happy to provide any additional information you might need.\n\n";
        $output .= "Looking forward to hearing from you.\n\n";

        $output .= "Note: This is a preview. Confirm with the user before updating the step.";

        return $output;
    }
}

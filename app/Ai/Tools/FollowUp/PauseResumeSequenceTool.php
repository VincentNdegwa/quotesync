<?php

namespace App\Ai\Tools\FollowUp;

use App\Models\FollowUpSequence;
use App\Models\User;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;

class PauseResumeSequenceTool implements Tool
{
    public function __construct(
        private readonly ?FollowUpSequence $sequence,
        private readonly User $user,
    ) {}

    public function description(): string
    {
        return 'Pauses or resumes a follow-up sequence for a specific quote/invoice or for all entities in that sequence. '
            . 'Requires confirmation. Useful when a client asks to be contacted later or a deal is being renegotiated.';
    }

    public function schema(JsonSchema $schema): array
    {
        if ($this->sequence) {
            return [
                'action' => $schema->string()
                    ->enum('pause', 'resume')
                    ->description('The action to perform.')
                    ->required(),
                'entity_id' => $schema->integer()
                    ->description('If provided, only pause for this specific quote/invoice.')
                    ->nullable(),
                'reason' => $schema->string()
                    ->description('Reason for pausing (stored as note).')
                    ->nullable(),
            ];
        }

        return [
            'sequence_id' => $schema->integer()
                ->description('The sequence ID.')
                ->required(),
            'action' => $schema->string()
                ->enum('pause', 'resume')
                ->description('The action to perform.')
                ->required(),
            'entity_id' => $schema->integer()
                ->description('If provided, only pause for this specific quote/invoice.')
                ->nullable(),
            'reason' => $schema->string()
                ->description('Reason for pausing (stored as note).')
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
        $action = $request['action'];
        $entityId = $request['entity_id'] ?? null;
        $reason = $request['reason'] ?? '';

        $output = "Sequence Action Preview for: {$sequence->name}\n";
        $output .= "===========================================\n";
        $output .= "Action: " . ucfirst($action) . "\n";
        $output .= "Current Status: " . ($sequence->is_active ? 'Active' : 'Paused') . "\n";

        if ($entityId) {
            $output .= "Entity ID: {$entityId}\n";
            $output .= "Scope: Specific entity only\n";
        } else {
            $output .= "Scope: All entities in sequence\n";
        }

        if ($reason) {
            $output .= "Reason: {$reason}\n";
        }

        $output .= "\nNote: This is a preview. Confirm with the user before applying the action.";

        return $output;
    }

    private function handleWorkspace(Request $request): string
    {
        $sequenceId = $request['sequence_id'];
        $action = $request['action'];
        $entityId = $request['entity_id'] ?? null;
        $reason = $request['reason'] ?? '';

        $sequence = \App\Models\FollowUpSequence::where('workspace_id', $this->user->current_workspace_id)
            ->find($sequenceId);

        if (!$sequence) {
            return "Sequence with ID {$sequenceId} not found.";
        }

        $output = "Sequence Action Preview for: {$sequence->name}\n";
        $output .= "===========================================\n";
        $output .= "Action: " . ucfirst($action) . "\n";
        $output .= "Current Status: " . ($sequence->is_active ? 'Active' : 'Paused') . "\n";

        if ($entityId) {
            $output .= "Entity ID: {$entityId}\n";
            $output .= "Scope: Specific entity only\n";
        } else {
            $output .= "Scope: All entities in sequence\n";
        }

        if ($reason) {
            $output .= "Reason: {$reason}\n";
        }

        $output .= "\nNote: This is a preview. Confirm with the user before applying the action.";

        return $output;
    }
}

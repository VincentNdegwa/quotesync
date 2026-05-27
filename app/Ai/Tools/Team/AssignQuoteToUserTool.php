<?php

namespace App\Ai\Tools\Team;

use App\Models\User;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;

class AssignQuoteToUserTool implements Tool
{
    public function __construct(
        private readonly User $user,
    ) {}

    public function description(): string
    {
        return 'Reassigns a quote to a different team member. Useful when redistributing workload '
            . 'or when a rep leaves. Requires confirmation.';
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'quote_id' => $schema->integer()
                ->description('The quote ID.')
                ->required(),
            'assigned_to' => $schema->integer()
                ->description('User ID to assign to.')
                ->required(),
            'reason' => $schema->string()
                ->description('Reason for reassignment.')
                ->nullable(),
        ];
    }

    public function handle(Request $request): string
    {
        $workspaceId = $this->user->current_workspace_id;

        if (!$workspaceId) {
            return 'Error: No active workspace found. Please select a workspace first.';
        }

        $quoteId = $request['quote_id'];
        $assignedTo = $request['assigned_to'];
        $reason = $request['reason'] ?? '';

        $quote = \App\Models\Quote::where('workspace_id', $workspaceId)->find($quoteId);

        if (!$quote) {
            return "Quote with ID {$quoteId} not found.";
        }

        $newAssignee = \App\Models\User::find($assignedTo);

        if (!$newAssignee) {
            return "User with ID {$assignedTo} not found.";
        }

        $currentAssignee = \App\Models\User::find($quote->assigned_to);

        $output = "Quote Reassignment Preview\n";
        $output .= "==========================\n";
        $output .= "Quote #{$quote->number} (ID: {$quote->id})\n";
        $output .= "Current Assignee: " . ($currentAssignee ? $currentAssignee->name : 'Unassigned') . "\n";
        $output .= "New Assignee: {$newAssignee->name}\n";

        if ($reason) {
            $output .= "Reason: {$reason}\n";
        }

        $output .= "\nNote: This is a preview. Confirm with the user before applying the reassignment.";

        return $output;
    }
}

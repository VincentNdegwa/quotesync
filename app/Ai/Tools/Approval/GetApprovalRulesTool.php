<?php

namespace App\Ai\Tools\Approval;

use App\Models\Quote;
use App\Models\User;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;

class GetApprovalRulesTool implements Tool
{
    public function __construct(
        private readonly ?Quote $quote,
        private readonly User $user,
    ) {}

    public function description(): string
    {
        return 'Returns all configured approval rules for the workspace: rule name, conditions '
            . '(e.g. total > X, discount > Y%, new client, specific catalog item), who must approve, '
            . 'and how many quotes have been triggered by each rule in the last 30 days.';
    }

    public function schema(JsonSchema $schema): array
    {
        return [];
    }

    public function handle(Request $request): string
    {
        $workspaceId = $this->user->current_workspace_id;

        if (!$workspaceId) {
            return 'Error: No active workspace found. Please select a workspace first.';
        }

        $rules = \App\Models\ApprovalRule::where('workspace_id', $workspaceId)->get();

        if ($rules->isEmpty()) {
            return "No approval rules configured for this workspace.";
        }

        $output = "Found {$rules->count()} approval rule(s):\n\n";
        foreach ($rules as $rule) {
            $output .= "- Rule: {$rule->name}\n";
            $output .= "  Conditions: " . ($rule->conditions ?? 'Not specified') . "\n";
            $output .= "  Required Approvers: " . ($rule->required_approvers ?? 1) . "\n";
            $output .= "  Active: " . ($rule->is_active ? 'Yes' : 'No') . "\n\n";
        }

        return $output;
    }
}

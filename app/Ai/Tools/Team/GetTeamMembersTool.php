<?php

namespace App\Ai\Tools\Team;

use App\Models\User;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;

class GetTeamMembersTool implements Tool
{
    public function __construct(
        private readonly User $user,
    ) {}

    public function description(): string
    {
        return 'Returns all users in the current workspace with their roles, and a lightweight '
            . 'activity summary (tasks completed this week, quotes sent, approvals actioned).';
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'include_activity' => $schema->boolean()
                ->description('Include activity summary. Default: true.')
                ->nullable(),
        ];
    }

    public function handle(Request $request): string
    {
        $workspaceId = $this->user->current_workspace_id;

        if (!$workspaceId) {
            return 'Error: No active workspace found. Please select a workspace first.';
        }

        $includeActivity = $request['include_activity'] ?? true;

        $teamMembers = \App\Models\User::where('current_workspace_id', $workspaceId)->get();

        if ($teamMembers->isEmpty()) {
            return "No team members found in this workspace.";
        }

        $output = "Team Members\n";
        $output .= "=============\n\n";

        foreach ($teamMembers as $member) {
            $output .= "- {$member->name} ({$member->email})\n";
            $output .= "  Role: " . ($member->role ?? 'Team Member') . "\n";

            if ($includeActivity) {
                $tasksCompleted = \App\Models\Task::where('workspace_id', $workspaceId)
                    ->where('assigned_to', $member->id)
                    ->where('status', 'completed')
                    ->where('updated_at', '>=', now()->subWeek())
                    ->count();

                $quotesSent = \App\Models\Quote::where('workspace_id', $workspaceId)
                    ->where('created_by', $member->id)
                    ->where('status', 'sent')
                    ->where('created_at', '>=', now()->subWeek())
                    ->count();

                $output .= "  Tasks completed this week: {$tasksCompleted}\n";
                $output .= "  Quotes sent this week: {$quotesSent}\n";
            }

            $output .= "\n";
        }

        return $output;
    }
}

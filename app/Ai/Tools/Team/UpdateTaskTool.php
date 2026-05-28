<?php

namespace App\Ai\Tools\Team;

use App\Models\User;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;

class UpdateTaskTool implements Tool
{
    public function __construct(
        private readonly User $user,
    ) {}

    public function description(): string
    {
        return 'Updates a task: mark complete, reassign, change due date, update priority. Returns the before/after for confirmation.';
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'task_id' => $schema->integer()
                ->description('The task ID.')
                ->required(),
            'fields' => $schema->object()
                ->description('Fields to update.')
                ->required(),
        ];
    }

    public function handle(Request $request): string
    {
        $workspaceId = $this->user->current_workspace_id;

        if (!$workspaceId) {
            return 'Error: No active workspace found. Please select a workspace first.';
        }

        $taskId = $request['task_id'];
        $fields = $request['fields'] ?? [];

        $task = \App\Models\Task::where('workspace_id', $workspaceId)->find($taskId);

        if (!$task) {
            return "Task with ID {$taskId} not found.";
        }

        $output = "Task Update Preview for Task ID {$taskId}\n";
        $output .= "========================================\n";
        $output .= "Current Title: {$task->title}\n";
        $output .= "Current Due: " . ($task->due_date ? $task->due_date->toFormattedDateString() : 'No due date') . "\n\n";

        $output .= "Proposed Changes:\n";
        foreach ($fields as $field => $value) {
            $output .= "- {$field}: {$value}\n";
        }

        $output .= "\nNote: This is a preview. Confirm with the user before applying the changes.";

        return $output;
    }
}

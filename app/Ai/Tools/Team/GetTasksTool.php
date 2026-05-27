<?php

namespace App\Ai\Tools\Team;

use App\Models\User;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;

class GetTasksTool implements Tool
{
    public function __construct(
        private readonly User $user,
    ) {}

    public function description(): string
    {
        return 'Returns tasks for the workspace. Filterable by assigned user, status, related entity '
            . '(quote/client/invoice), due date range, and priority.';
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'assigned_to' => $schema->integer()
                ->description('User ID to filter by.')
                ->nullable(),
            'status' => $schema->string()
                ->enum('open', 'in_progress', 'completed', 'all')
                ->description('Task status. Default: open.')
                ->nullable(),
            'due_before' => $schema->string()
                ->description('ISO date filter.')
                ->nullable(),
            'entity_type' => $schema->string()
                ->enum('quote', 'client', 'invoice')
                ->description('Filter by entity type.')
                ->nullable(),
            'entity_id' => $schema->integer()
                ->description('Filter by entity ID.')
                ->nullable(),
            'limit' => $schema->integer()
                ->min(1)
                ->max(100)
                ->description('Maximum number of tasks. Default 20.')
                ->nullable(),
        ];
    }

    public function handle(Request $request): string
    {
        $workspaceId = $this->user->current_workspace_id;

        if (!$workspaceId) {
            return 'Error: No active workspace found. Please select a workspace first.';
        }

        $assignedTo = $request['assigned_to'] ?? null;
        $status = $request['status'] ?? 'open';
        $dueBefore = $request['due_before'] ?? null;
        $entityType = $request['entity_type'] ?? null;
        $entityId = $request['entity_id'] ?? null;
        $limit = $request['limit'] ?? 20;

        $query = \App\Models\Task::where('workspace_id', $workspaceId);

        if ($assignedTo) {
            $query->where('assigned_to', $assignedTo);
        }

        if ($status !== 'all') {
            $query->where('status', $status);
        }

        if ($dueBefore) {
            $query->where('due_date', '<=', $dueBefore);
        }

        if ($entityType && $entityId) {
            $query->where('entity_type', $entityType)->where('entity_id', $entityId);
        }

        $tasks = $query->with('assignedUser')->orderBy('due_date', 'asc')->limit($limit)->get();

        if ($tasks->isEmpty()) {
            return "No tasks found matching the criteria.";
        }

        $output = "Found {$tasks->count()} task(s):\n\n";
        foreach ($tasks as $task) {
            $assignee = $task->assignedUser ? $task->assignedUser->name : 'Unassigned';
            $output .= "- {$task->title}\n";
            $output .= "  Status: {$task->status}\n";
            $output .= "  Assigned to: {$assignee}\n";
            $output .= "  Due: " . ($task->due_date ? $task->due_date->toFormattedDateString() : 'No due date') . "\n";
            $output .= "  Priority: " . ($task->priority ?? 'medium') . "\n\n";
        }

        return $output;
    }
}

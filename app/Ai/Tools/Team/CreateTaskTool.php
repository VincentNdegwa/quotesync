<?php

namespace App\Ai\Tools\Team;

use App\Models\User;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;

class CreateTaskTool implements Tool
{
    public function __construct(
        private readonly User $user,
    ) {}

    public function description(): string
    {
        return 'Creates a task and assigns it to a team member. Links it to a related entity (quote, client, or invoice) if applicable. Requires confirmation before creating.';
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'title' => $schema->string()
                ->description('Task title.')
                ->required(),
            'description' => $schema->string()
                ->description('Task description.')
                ->nullable(),
            'assigned_to' => $schema->integer()
                ->description('User ID to assign to.')
                ->required(),
            'due_date' => $schema->string()
                ->description('ISO date.')
                ->nullable(),
            'priority' => $schema->string()
                ->enum('low', 'medium', 'high')
                ->description('Task priority. Default: medium.')
                ->nullable(),
            'entity_type' => $schema->string()
                ->enum('quote', 'client', 'invoice')
                ->description('Related entity type.')
                ->nullable(),
            'entity_id' => $schema->integer()
                ->description('Related entity ID.')
                ->nullable(),
        ];
    }

    public function handle(Request $request): string
    {
        $workspaceId = $this->user->current_workspace_id;

        if (!$workspaceId) {
            return 'Error: No active workspace found. Please select a workspace first.';
        }

        $title = $request['title'];
        $description = $request['description'] ?? '';
        $assignedTo = $request['assigned_to'];
        $dueDate = $request['due_date'] ?? null;
        $priority = $request['priority'] ?? 'medium';
        $entityType = $request['entity_type'] ?? null;
        $entityId = $request['entity_id'] ?? null;

        $assignee = \App\Models\User::find($assignedTo);

        if (!$assignee) {
            return "User with ID {$assignedTo} not found.";
        }

        $output = "Task Creation Preview\n";
        $output .= "======================\n";
        $output .= "Title: {$title}\n";
        $output .= "Description: {$description}\n";
        $output .= "Assigned to: {$assignee->name}\n";
        $output .= "Due: " . ($dueDate ? $dueDate : 'No due date') . "\n";
        $output .= "Priority: {$priority}\n";

        if ($entityType && $entityId) {
            $output .= "Linked to: {$entityType} ID {$entityId}\n";
        }

        $output .= "\nNote: This is a preview. Confirm with the user before creating the task.";

        return $output;
    }
}

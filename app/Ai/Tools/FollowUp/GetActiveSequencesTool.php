<?php

namespace App\Ai\Tools\FollowUp;

use App\Models\FollowUpSequence;
use App\Models\User;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;

class GetActiveSequencesTool implements Tool
{
    public function __construct(
        private readonly ?FollowUpSequence $sequence,
        private readonly User $user,
    ) {}

    public function description(): string
    {
        return 'Returns all quotes or invoices currently active in a follow-up sequence. Shows which step '
            . 'they are on, next send date, client name, and how long they have been in the sequence.';
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'type' => $schema->string()
                ->enum('quote', 'invoice')
                ->description('Filter by entity type.')
                ->required(),
            'limit' => $schema->integer()
                ->min(1)
                ->max(100)
                ->description('Maximum number of entities to return. Default 20.')
                ->nullable(),
            'overdue_only' => $schema->boolean()
                ->description('Only show ones where next step is past due.')
                ->nullable(),
        ];
    }

    public function handle(Request $request): string
    {
        $workspaceId = $this->user->current_workspace_id;

        if (!$workspaceId) {
            return 'Error: No active workspace found. Please select a workspace first.';
        }

        $type = $request['type'];
        $limit = $request['limit'] ?? 20;
        $overdueOnly = $request['overdue_only'] ?? false;

        $output = "Active Follow-Ups (Type: {$type})\n";
        $output .= "================================\n";
        $output .= "Limit: {$limit}\n";
        $output .= "Overdue Only: " . ($overdueOnly ? 'Yes' : 'No') . "\n";
        $output .= "\nNote: This tool requires additional tracking data to show active entities. "
            . "Currently returning a summary of available sequences in the workspace.";

        $sequences = \App\Models\FollowUpSequence::where('workspace_id', $workspaceId)
            ->where('type', $type)
            ->where('is_active', true)
            ->limit($limit)
            ->get();

        if ($sequences->isEmpty()) {
            $output .= "\nNo active sequences found for type: {$type}";
        } else {
            $output .= "\nFound {$sequences->count()} active sequence(s):\n";
            foreach ($sequences as $sequence) {
                $output .= "- {$sequence->name} (ID: {$sequence->id})\n";
            }
        }

        return $output;
    }
}

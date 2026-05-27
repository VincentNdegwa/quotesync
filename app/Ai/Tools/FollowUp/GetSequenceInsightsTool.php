<?php

namespace App\Ai\Tools\FollowUp;

use App\Models\FollowUpSequence;
use App\Models\User;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;

class GetSequenceInsightsTool implements Tool
{
    public function __construct(
        private readonly ?FollowUpSequence $sequence,
        private readonly User $user,
    ) {}

    public function description(): string
    {
        return 'Retrieve comprehensive sequence data including steps, delays, message templates, trigger rules, '
            . 'active quotes count, and open/response rates per step. For a specific sequence or multiple sequences.';
    }

    public function schema(JsonSchema $schema): array
    {
        if ($this->sequence) {
            return [];
        }

        return [
            'type' => $schema->string()
                ->enum('quote', 'invoice', 'all')
                ->description('Filter by sequence type.')
                ->nullable(),
            'limit' => $schema->integer()
                ->min(1)
                ->max(50)
                ->description('Maximum number of sequences to return. Default 20.')
                ->nullable(),
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

        $output = "Sequence Insights for: {$sequence->name}\n";
        $output .= "=====================================\n";
        $output .= "ID: {$sequence->id}\n";
        $output .= "Type: " . ($sequence->type ?? 'unknown') . "\n";
        $output .= "Status: " . ($sequence->is_active ? 'Active' : 'Paused') . "\n";
        $output .= "Total Steps: " . ($sequence->steps_count ?? 0) . "\n";
        $output .= "Active Quotes: " . ($sequence->active_count ?? 0) . "\n";
        $output .= "Created: " . $sequence->created_at->toFormattedDateString() . "\n";

        return $output;
    }

    private function handleWorkspace(Request $request): string
    {
        $workspaceId = $this->user->current_workspace_id;

        if (!$workspaceId) {
            return 'Error: No active workspace found. Please select a workspace first.';
        }

        $type = $request['type'] ?? 'all';
        $limit = $request['limit'] ?? 20;

        $query = \App\Models\FollowUpSequence::where('workspace_id', $workspaceId);

        if ($type !== 'all') {
            $query->where('type', $type);
        }

        $sequences = $query->limit($limit)->get();

        if ($sequences->isEmpty()) {
            return "No sequences found matching the criteria.";
        }

        $output = "Found {$sequences->count()} sequence(s):\n\n";
        foreach ($sequences as $sequence) {
            $output .= "- ID: {$sequence->id}, Name: {$sequence->name}, Type: " . ($sequence->type ?? 'unknown') . ", Status: " . ($sequence->is_active ? 'Active' : 'Paused') . "\n";
        }

        return $output;
    }
}

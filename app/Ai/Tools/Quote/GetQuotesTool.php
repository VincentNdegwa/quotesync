<?php

namespace App\Ai\Tools\Quote;

use App\Models\Quote;
use App\Models\User;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

class GetQuotesTool implements Tool
{
    public function __construct(public User $user) {}

    /**
     * Get the description of the tool's purpose.
     */
    public function description(): Stringable|string
    {
        return 'Retrieve quotes from the system. Can filter by status (draft, sent, accepted, rejected, expired) or search by client name.';
    }

    /**
     * Execute the tool.
     */
    public function handle(Request $request): Stringable|string
    {
        $workspaceId = $this->user->current_workspace_id;

        if (!$workspaceId) {
            return 'Error: No active workspace found. Please select a workspace first.';
        }

        $query = Quote::where('workspace_id', $workspaceId);

        if (!empty($request['status'])) {
            $query->where('status', $request['status']);
        }

        if (!empty($request['search'])) {
            $search = $request['search'];
            $query->whereHas('client', function ($q) use ($search) {
                $q->where('company_name', 'like', "%{$search}%");
            });
        }

        $quotes = $query->with('client')->limit(20)->get();

        if ($quotes->isEmpty()) {
            return 'No quotes found.';
        }

        $output = "Found {$quotes->count()} quote(s):\n\n";
        foreach ($quotes as $quote) {
            $clientName = $quote->client ? $quote->client->company_name : 'Unknown';
            $output .= "- ID: {$quote->id}, Client: {$clientName}, Status: {$quote->status}, Total: {$quote->total}\n";
        }

        return $output;
    }

    /**
     * Get the tool's schema definition.
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'status' => $schema->string()->nullable()->description('Filter by quote status (draft, sent, accepted, rejected, expired)'),
            'search' => $schema->string()->nullable()->description('Search term to filter quotes by client company name'),
        ];
    }
}

<?php

namespace App\Ai\Tools\Client;

use App\Models\Client;
use App\Models\User;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

class GetClientsTool implements Tool
{
    public function __construct(public User $user) {}

    /**
     * Get the description of the tool's purpose.
     */
    public function description(): Stringable|string
    {
        return 'Retrieve clients from the system. Can search by name, email, or list all clients.';
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

        $query = Client::where('workspace_id', $workspaceId);

        if (!empty($request['search'])) {
            $search = $request['search'];
            $query->where(function ($q) use ($search) {
                $q->where('company_name', 'like', "%{$search}%")
                  ->orWhere('contact_name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $clients = $query->limit(20)->get();

        if ($clients->isEmpty()) {
            return 'No clients found.';
        }

        $output = "Found {$clients->count()} client(s):\n\n";
        foreach ($clients as $client) {
            $output .= "- ID: {$client->id}, Company: {$client->company_name}, Contact: {$client->contact_name}, Email: {$client->email}\n";
        }

        return $output;
    }

    /**
     * Get the tool's schema definition.
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'search' => $schema->string()->nullable()->description('Search term to filter clients by company name, contact name, or email'),
        ];
    }
}

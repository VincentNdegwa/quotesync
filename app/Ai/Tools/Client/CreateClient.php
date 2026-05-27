<?php

namespace App\Ai\Tools\Client;

use App\Models\Client;
use App\Models\User;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

class CreateClient implements Tool
{
    public function __construct(public User $user) {}

    /**
     * Get the description of the tool's purpose.
     */
    public function description(): Stringable|string
    {
        return 'Create a new client in the system. Use this when the user wants to add a new client.';
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

        $client = Client::create([
            'workspace_id' => $workspaceId,
            'company_name' => $request['company_name'],
            'contact_name' => $request['contact_name'] ?? null,
            'email' => $request['email'] ?? null,
            'phone' => $request['phone'] ?? null,
            'address' => $request['address'] ?? null,
            'city' => $request['city'] ?? null,
            'country' => $request['country'] ?? null,
            'currency' => $request['currency'] ?? 'USD',
            'language' => $request['language'] ?? 'en',
            'tax_number' => $request['tax_number'] ?? null,
            'created_by' => $this->user->id,
        ]);

        return "Client '{$client->company_name}' created successfully with ID: {$client->id}";
    }

    /**
     * Get the tool's schema definition.
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'company_name' => $schema->string()->required()->description('The name of the client company'),
            'contact_name' => $schema->string()->nullable()->description('The primary contact person name'),
            'email' => $schema->string()->format('email')->nullable()->description('Client email address'),
            'phone' => $schema->string()->nullable()->description('Client phone number'),
            'address' => $schema->string()->nullable()->description('Client street address'),
            'city' => $schema->string()->nullable()->description('Client city'),
            'country' => $schema->string()->nullable()->description('Client country'),
            'currency' => $schema->string()->nullable()->description('Client preferred currency code (e.g., USD, EUR)'),
            'language' => $schema->string()->nullable()->description('Client preferred language code (e.g., en, es)'),
            'tax_number' => $schema->string()->nullable()->description('Client tax/VAT number'),
        ];
    }
}

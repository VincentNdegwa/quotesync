<?php

namespace App\Ai\Tools\Client;

use App\Models\Client;
use App\Models\User;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Facades\Log;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;

class UpdateClientProfileTool implements Tool
{
    public function __construct(
        private readonly Client $client,
        private readonly User $user,
    ) {}

    public function description(): string
    {
        return 'Update one or more fields on the client\'s profile. '
            . 'Only update fields the user has explicitly asked to change or confirmed. '
            . 'Do NOT update fields without user confirmation. '
            . 'Returns the updated fields and their new values.';
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'fields' => $schema->object(fn ($s) => [
                'company_name' => $s->string()->description('Client company name'),
                'contact_name' => $s->string()->description('Primary contact full name'),
                'email' => $s->string()->description('Client email address'),
                'phone' => $s->string()->description('Client phone number'),
                'whatsapp' => $s->string()->description('Client WhatsApp number'),
                'address' => $s->string()->description('Street address'),
                'city' => $s->string()->description('City'),
                'country' => $s->string()->description('Country'),
                'currency' => $s->string()->description('3-letter currency code e.g. USD, KES, GBP'),
                'language' => $s->string()->description('Language code e.g. en, sw'),
                'tax_number' => $s->string()->description('Tax / VAT registration number'),
            ])->description('Object of fields to update. Only include fields that should change.')
                ->required(),
        ];
    }

    public function handle(Request $request): string
    {
        $fields = $request['fields'] ?? [];

        if (empty($fields)) {
            return json_encode(['error' => 'No fields provided to update.']);
        }

        $allowed = [
            'company_name', 'contact_name', 'email', 'phone', 'whatsapp',
            'address', 'city', 'country', 'currency', 'language', 'tax_number',
        ];

        $toUpdate = array_intersect_key($fields, array_flip($allowed));

        if (empty($toUpdate)) {
            return json_encode(['error' => 'No valid fields to update.']);
        }

        $before = $this->client->only(array_keys($toUpdate));

        $this->client->update($toUpdate);

        Log::info('ClientAgent updated client profile', [
            'client_id' => $this->client->id,
            'changes' => $toUpdate,
        ]);

        return json_encode([
            'success' => true,
            'client_id' => $this->client->id,
            'updated_fields' => $toUpdate,
            'previous_values' => $before,
            'message' => 'Profile updated successfully.',
        ], JSON_PRETTY_PRINT);
    }
}

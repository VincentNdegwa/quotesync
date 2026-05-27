<?php

use App\Ai\Tools\Client\UpdateClientProfileTool;
use App\Models\Client;
use App\Models\User;
use App\Models\Workspace;
use Laravel\Ai\Tools\Request;

beforeEach(function () {
    $this->workspace = Workspace::factory()->create();
    $this->user = User::factory()->create([
        'name' => 'Test User',
        'email' => 'test@example.com',
        'current_workspace_id' => $this->workspace->id,
    ]);

    $this->client = Client::factory()->create([
        'workspace_id' => $this->workspace->id,
        'company_name' => 'ACME Corp',
        'contact_name' => 'John Doe',
        'email' => 'john@acme.com',
        'phone' => '1234567890',
    ]);
});

it('updates client profile fields', function () {
    $tool = new UpdateClientProfileTool($this->client, $this->user);
    $result = $tool->handle(new Request([
        'fields' => [
            'company_name' => 'Updated Corp',
            'contact_name' => 'Jane Smith',
        ],
    ]));

    $data = json_decode($result, true);

    expect($data['success'])->toBeTrue()
        ->and($data['updated_fields']['company_name'])->toBe('Updated Corp')
        ->and($data['updated_fields']['contact_name'])->toBe('Jane Smith');

    $this->client->refresh();
    expect($this->client->company_name)->toBe('Updated Corp')
        ->and($this->client->contact_name)->toBe('Jane Smith');
});

it('returns error when no fields provided', function () {
    $tool = new UpdateClientProfileTool($this->client, $this->user);
    $result = $tool->handle(new Request([
        'fields' => [],
    ]));

    $data = json_decode($result, true);

    expect($data)->toHaveKey('error');
});

it('returns error when no valid fields to update', function () {
    $tool = new UpdateClientProfileTool($this->client, $this->user);
    $result = $tool->handle(new Request([
        'fields' => [
            'invalid_field' => 'value',
        ],
    ]));

    $data = json_decode($result, true);

    expect($data)->toHaveKey('error');
});

it('only updates allowed fields', function () {
    $tool = new UpdateClientProfileTool($this->client, $this->user);
    $result = $tool->handle(new Request([
        'fields' => [
            'company_name' => 'Updated Corp',
            'invalid_field' => 'should not update',
        ],
    ]));

    $data = json_decode($result, true);

    expect($data['success'])->toBeTrue()
        ->and($data['updated_fields'])->toHaveKey('company_name')
        ->and($data['updated_fields'])->not->toHaveKey('invalid_field');
});

it('returns previous values in response', function () {
    $tool = new UpdateClientProfileTool($this->client, $this->user);
    $result = $tool->handle(new Request([
        'fields' => [
            'company_name' => 'Updated Corp',
        ],
    ]));

    $data = json_decode($result, true);

    expect($data['previous_values']['company_name'])->toBe('ACME Corp');
});

it('updates multiple fields at once', function () {
    $tool = new UpdateClientProfileTool($this->client, $this->user);
    $result = $tool->handle(new Request([
        'fields' => [
            'company_name' => 'Updated Corp',
            'contact_name' => 'Jane Smith',
            'email' => 'jane@example.com',
            'phone' => '9876543210',
        ],
    ]));

    $data = json_decode($result, true);

    expect($data['updated_fields'])->toHaveCount(4);

    $this->client->refresh();
    expect($this->client->company_name)->toBe('Updated Corp')
        ->and($this->client->contact_name)->toBe('Jane Smith')
        ->and($this->client->email)->toBe('jane@example.com')
        ->and($this->client->phone)->toBe('9876543210');
});

it('returns correct description', function () {
    $tool = new UpdateClientProfileTool($this->client, $this->user);

    expect($tool->description())->toContain('update')
        ->toContain('profile')
        ->toContain('fields');
});


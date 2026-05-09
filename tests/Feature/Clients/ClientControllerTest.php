<?php

use App\Models\Client;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('can create a client via controller', function () {
    $user = User::factory()->create();
    $workspace = $user->currentWorkspace;
    $user->addRole('admin', $workspace);

    $payload = [
        'company_name' => 'Test Company',
        'contact_name' => 'John Doe',
        'email' => 'john@example.com',
        'phone' => '+1234567890',
        'country' => 'US',
        'currency' => 'USD',
    ];

    $response = $this->actingAs($user)
        ->post(route('clients.store'), $payload);

    $response->assertRedirect();

    $this->assertDatabaseHas('clients', [
        'workspace_id' => $workspace->id,
        'company_name' => 'Test Company',
        'email' => 'john@example.com',
    ]);
});

it('validates client creation via controller', function () {
    $user = User::factory()->create();
    $workspace = $user->currentWorkspace;
    $user->addRole('admin', $workspace);

    $payload = [
        'company_name' => '',
        'email' => 'invalid-email',
    ];

    $response = $this->actingAs($user)
        ->post(route('clients.store'), $payload);

    $response->assertSessionHasErrors(['company_name', 'email']);
});

it('can update a client via controller', function () {
    $user = User::factory()->create();
    $workspace = $user->currentWorkspace;
    $user->addRole('admin', $workspace);
    $client = Client::factory()->create(['workspace_id' => $workspace->id]);

    $payload = [
        'company_name' => 'Updated Company',
        'contact_name' => 'Jane Doe',
        'email' => 'jane@example.com',
    ];

    $response = $this->actingAs($user)
        ->put(route('clients.update', $client), $payload);

    $response->assertRedirect();

    $this->assertDatabaseHas('clients', [
        'id' => $client->id,
        'company_name' => 'Updated Company',
        'email' => 'jane@example.com',
    ]);
});

it('can delete a client via controller', function () {
    $user = User::factory()->create();
    $workspace = $user->currentWorkspace;
    $user->addRole('admin', $workspace);
    $client = Client::factory()->create(['workspace_id' => $workspace->id]);

    $response = $this->actingAs($user)
        ->delete(route('clients.destroy', $client));

    $response->assertRedirect();

    $client->refresh();
    expect($client->deleted_at)->not->toBeNull();
});

it('can bulk delete clients via controller', function () {
    $user = User::factory()->create();
    $workspace = $user->currentWorkspace;
    $user->addRole('admin', $workspace);
    $client1 = Client::factory()->create(['workspace_id' => $workspace->id]);
    $client2 = Client::factory()->create(['workspace_id' => $workspace->id]);
    $client3 = Client::factory()->create(['workspace_id' => $workspace->id]);

    $payload = [
        'ids' => [$client1->id, $client2->id],
    ];

    $response = $this->actingAs($user)
        ->post(route('clients.bulk-delete'), $payload);

    $response->assertRedirect();

    $client1->refresh();
    $client2->refresh();
    $client3->refresh();
    expect($client1->deleted_at)->not->toBeNull();
    expect($client2->deleted_at)->not->toBeNull();
    expect($client3->deleted_at)->toBeNull();
});

it('can view a client via controller', function () {
    $user = User::factory()->create();
    $workspace = $user->currentWorkspace;
    $client = Client::factory()->create(['workspace_id' => $workspace->id]);

    $response = $this->actingAs($user)
        ->get(route('clients.show', $client));

    $response->assertStatus(200);
});

it('cannot access client from another workspace via controller', function () {
    $userA = User::factory()->create();
    $workspaceA = $userA->currentWorkspace;
    $clientA = Client::factory()->create(['workspace_id' => $workspaceA->id]);

    $userB = User::factory()->create();

    $response = $this->actingAs($userB)
        ->get(route('clients.show', $clientA));

    $response->assertNotFound();
});

it('cannot update client from another workspace via controller', function () {
    $userA = User::factory()->create();
    $workspaceA = $userA->currentWorkspace;
    $clientA = Client::factory()->create(['workspace_id' => $workspaceA->id]);

    $userB = User::factory()->create();

    $payload = ['company_name' => 'Hacked'];

    $response = $this->actingAs($userB)
        ->put(route('clients.update', $clientA), $payload);

    $response->assertNotFound();
});

it('cannot delete client from another workspace via controller', function () {
    $userA = User::factory()->create();
    $workspaceA = $userA->currentWorkspace;
    $clientA = Client::factory()->create(['workspace_id' => $workspaceA->id]);

    $userB = User::factory()->create();

    $response = $this->actingAs($userB)
        ->delete(route('clients.destroy', $clientA));

    $response->assertNotFound();

    $this->assertDatabaseHas('clients', ['id' => $clientA->id]);
});

it('can export clients to csv via controller', function () {
    $user = User::factory()->create();
    $workspace = $user->currentWorkspace;
    Client::factory()->create([
        'workspace_id' => $workspace->id,
        'company_name' => 'Test Company',
        'email' => 'test@example.com',
    ]);

    $response = $this->actingAs($user)
        ->get(route('clients.export.csv'));

    $response->assertStatus(200);
    $response->assertHeader('content-type', 'text/csv; charset=utf-8');
});

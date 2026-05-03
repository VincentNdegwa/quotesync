<?php

use App\Models\Client;
use App\Models\Quote;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('can create a quote version', function () {
    $user = User::factory()->create();
    $workspace = $user->currentWorkspace;
    $client = Client::factory()->create(['workspace_id' => $workspace->id]);

    $parentQuote = Quote::factory()->create([
        'workspace_id' => $workspace->id,
        'client_id' => $client->id,
        'created_by' => $user->id,
        'version' => 1,
    ]);

    $version = Quote::factory()->create([
        'workspace_id' => $workspace->id,
        'client_id' => $client->id,
        'created_by' => $user->id,
        'parent_quote_id' => $parentQuote->id,
        'version' => 2,
    ]);

    expect($version->parent_quote_id)->toBe($parentQuote->id)
        ->and($version->version)->toBe(2)
        ->and($parentQuote->versions)->toHaveCount(1)
        ->and($parentQuote->versions->first()->id)->toBe($version->id);
});

it('can restore a quote from a previous version', function () {
    $user = User::factory()->create();
    $workspace = $user->currentWorkspace;
    $client = Client::factory()->create(['workspace_id' => $workspace->id]);

    $originalQuote = Quote::factory()->create([
        'workspace_id' => $workspace->id,
        'client_id' => $client->id,
        'created_by' => $user->id,
        'version' => 1,
        'title' => 'Original Quote',
        'subtotal' => 1000,
    ]);

    $versionQuote = Quote::factory()->create([
        'workspace_id' => $workspace->id,
        'client_id' => $client->id,
        'created_by' => $user->id,
        'parent_quote_id' => $originalQuote->id,
        'version' => 2,
        'title' => 'Version 2 Quote',
        'subtotal' => 1500,
    ]);

    // Restore from version
    $originalQuote->update([
        'title' => $versionQuote->title,
        'subtotal' => $versionQuote->subtotal,
    ]);

    expect($originalQuote->fresh()->title)->toBe('Version 2 Quote')
        ->and($originalQuote->fresh()->subtotal)->toBe('1500.00');
});

it('can get all versions of a quote', function () {
    $user = User::factory()->create();
    $workspace = $user->currentWorkspace;
    $client = Client::factory()->create(['workspace_id' => $workspace->id]);

    $parentQuote = Quote::factory()->create([
        'workspace_id' => $workspace->id,
        'client_id' => $client->id,
        'created_by' => $user->id,
        'version' => 1,
    ]);

    $version1 = Quote::factory()->create([
        'workspace_id' => $workspace->id,
        'client_id' => $client->id,
        'created_by' => $user->id,
        'parent_quote_id' => $parentQuote->id,
        'version' => 2,
    ]);

    $version2 = Quote::factory()->create([
        'workspace_id' => $workspace->id,
        'client_id' => $client->id,
        'created_by' => $user->id,
        'parent_quote_id' => $parentQuote->id,
        'version' => 3,
    ]);

    expect($parentQuote->versions)->toHaveCount(2)
        ->and($parentQuote->versions->first()->version)->toBe(3)
        ->and($parentQuote->versions->last()->version)->toBe(2);
});

it('can scope quotes to parents only', function () {
    $user = User::factory()->create();
    $workspace = $user->currentWorkspace;
    $client = Client::factory()->create(['workspace_id' => $workspace->id]);

    $parentQuote = Quote::factory()->create([
        'workspace_id' => $workspace->id,
        'client_id' => $client->id,
        'created_by' => $user->id,
        'version' => 1,
    ]);

    $versionQuote = Quote::factory()->create([
        'workspace_id' => $workspace->id,
        'client_id' => $client->id,
        'created_by' => $user->id,
        'parent_quote_id' => $parentQuote->id,
        'version' => 2,
    ]);

    $parentQuotes = Quote::parents()->get();

    expect($parentQuotes)->toHaveCount(1)
        ->and($parentQuotes->first()->id)->toBe($parentQuote->id);
});

it('can set an active version of a quote', function () {
    $user = User::factory()->create();
    $workspace = $user->currentWorkspace;
    $client = Client::factory()->create(['workspace_id' => $workspace->id]);

    $parentQuote = Quote::factory()->create([
        'workspace_id' => $workspace->id,
        'client_id' => $client->id,
        'created_by' => $user->id,
        'version' => 1,
    ]);

    $versionQuote = Quote::factory()->create([
        'workspace_id' => $workspace->id,
        'client_id' => $client->id,
        'created_by' => $user->id,
        'parent_quote_id' => $parentQuote->id,
        'version' => 2,
    ]);

    $parentQuote->update(['active_version_id' => $versionQuote->id]);

    expect($parentQuote->fresh()->active_version_id)->toBe($versionQuote->id)
        ->and($parentQuote->activeVersion->id)->toBe($versionQuote->id);
});

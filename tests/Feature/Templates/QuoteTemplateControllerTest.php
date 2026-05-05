<?php

use App\Models\QuoteTemplate;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('can create a quote template via controller', function () {
    $user = User::factory()->create();
    $workspace = $user->currentWorkspace;
    $user->addRole('admin', $workspace);

    $payload = [
        'title' => 'Test Template',
        'description' => 'Test Description',
        'industry' => 'Technology',
        'is_active' => true,
        'sections' => [
            [
                'title' => 'Services',
                'sort_order' => 0,
                'line_items' => [],
            ],
        ],
    ];

    $response = $this->actingAs($user)
        ->post(route('quote-templates.store'), $payload);

    $response->assertRedirect();

    $this->assertDatabaseHas('quote_templates', [
        'workspace_id' => $workspace->id,
        'name' => 'Test Template',
    ]);
});

it('can update a quote template via controller', function () {
    $user = User::factory()->create();
    $workspace = $user->currentWorkspace;
    $user->addRole('admin', $workspace);
    $template = QuoteTemplate::create([
        'workspace_id' => $workspace->id,
        'name' => 'Original Template',
        'description' => 'Original Description',
        'is_active' => true,
    ]);

    $payload = [
        'title' => 'Updated Template',
        'description' => 'Updated Description',
        'sections' => [
            [
                'title' => 'Services',
                'sort_order' => 0,
                'line_items' => [],
            ],
        ],
    ];

    $response = $this->actingAs($user)
        ->put(route('quote-templates.update', $template), $payload);

    $response->assertRedirect();

    $template->refresh();
    expect($template->name)->toBe('Updated Template');
});

it('can delete a quote template via controller', function () {
    $user = User::factory()->create();
    $workspace = $user->currentWorkspace;
    $user->addRole('admin', $workspace);
    $template = QuoteTemplate::create([
        'workspace_id' => $workspace->id,
        'name' => 'Test Template',
        'is_active' => true,
    ]);

    $response = $this->actingAs($user)
        ->delete(route('quote-templates.destroy', $template));

    $response->assertRedirect();

    $this->assertDatabaseMissing('quote_templates', ['id' => $template->id]);
});

it('can get template layout via controller', function () {
    $user = User::factory()->create();
    $workspace = $user->currentWorkspace;
    $template = QuoteTemplate::create([
        'workspace_id' => $workspace->id,
        'name' => 'Test Template',
        'is_active' => true,
    ]);

    $response = $this->actingAs($user)
        ->getJson(route('quote-templates.layout', $template));

    $response->assertStatus(200);
});

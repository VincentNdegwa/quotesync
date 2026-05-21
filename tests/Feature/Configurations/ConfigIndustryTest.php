<?php

use App\Models\ConfigIndustry;
use App\Models\User;

beforeEach(function () {
    $user = User::factory()->create();
    $workspace = $user->currentWorkspace;
    $this->actingAs($user)->currentWorkspace = $workspace;
    $this->workspace = $workspace;
    $this->user = $user;
});

test('user can create industry', function () {
    $response = $this->post('/configuration/industries', [
        'name' => 'Technology',
        'description' => 'Software and IT companies',
        'icon' => 'code',
        'color' => '#3b82f6',
        'is_active' => true,
    ]);

    $response->assertRedirect();
    $this->assertDatabaseHas('config_industries', [
        'name' => 'Technology',
        'description' => 'Software and IT companies',
        'icon' => 'code',
        'color' => '#3b82f6',
    ]);
});

test('user can update industry', function () {
    $industry = ConfigIndustry::query()->create([
        'workspace_id' => $this->workspace->id,
        'created_by' => $this->user->id,
        'name' => 'Test Industry',
        'description' => 'Test description',
        'icon' => 'code',
        'color' => '#3b82f6',
        'is_active' => true,
    ]);

    $response = $this->put("/configuration/industries/{$industry->id}", [
        'name' => 'Updated Technology',
        'description' => 'Updated description',
        'icon' => 'briefcase',
        'color' => '#10b981',
        'is_active' => false,
    ]);

    $response->assertRedirect();
    $this->assertDatabaseHas('config_industries', [
        'id' => $industry->id,
        'name' => 'Updated Technology',
        'description' => 'Updated description',
        'icon' => 'briefcase',
        'color' => '#10b981',
        'is_active' => false,
    ]);
});

test('user can delete industry', function () {
    $industry = ConfigIndustry::query()->create([
        'workspace_id' => $this->workspace->id,
        'created_by' => $this->user->id,
        'name' => 'Test Industry',
        'description' => 'Test description',
        'icon' => 'code',
        'color' => '#3b82f6',
        'is_active' => true,
    ]);

    $response = $this->delete("/configuration/industries/{$industry->id}");

    $response->assertRedirect();
    $this->assertSoftDeleted('config_industries', [
        'id' => $industry->id,
    ]);
});

test('industry validation requires name', function () {
    $response = $this->post('/configuration/industries', [
        'name' => '',
    ]);

    $response->assertSessionHasErrors(['name']);
});

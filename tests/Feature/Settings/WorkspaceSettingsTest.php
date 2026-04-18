<?php

use App\Models\User;
use App\Models\WorkspaceSetting;
use Inertia\Testing\AssertableInertia as Assert;

test('workspace settings page renders visible groups', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get('/business-setup/brand')
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component('settings/WorkspaceSettings')
            ->where('currentGroup.group', 'brand')
            ->has('groups')
            ->where('groups.0.key', 'brand')
            ->where('groups', fn ($groups): bool => ! collect($groups)->pluck('key')->contains('integrations')),
        );
});

test('workspace settings can be updated for a dynamic group', function () {
    $user = User::factory()->create();
    $workspace = $user->currentWorkspace;

    $response = $this->actingAs($user)
        ->put('/business-setup/localization', [
            'settings' => [
                'country' => 'NG',
                'timezone' => 'Africa/Lagos',
                'currency' => 'NGN',
                'date_format' => 'DD/MM/YYYY',
            ],
        ]);

    $response->assertRedirect();

    $this->assertDatabaseHas((new WorkspaceSetting)->getTable(), [
        'workspace_id' => $workspace->id,
        'group' => 'localization',
        'key' => 'country',
        'value' => 'NG',
    ]);

    $this->assertDatabaseHas((new WorkspaceSetting)->getTable(), [
        'workspace_id' => $workspace->id,
        'group' => 'localization',
        'key' => 'timezone',
        'value' => 'Africa/Lagos',
    ]);
});

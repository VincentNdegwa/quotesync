<?php

use App\Models\User;
use App\Models\WorkspaceSetting;
use Inertia\Testing\AssertableInertia as Assert;

test('dashboard redirects to onboarding when workspace settings are incomplete', function () {
    $user = User::factory()->create();
    $workspace = $user->currentWorkspace;

    $workspace->forceFill(['settings_onboarded_at' => null])->save();

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertRedirect(route('business-setup.onboarding'));
});

test('workspace onboarding page renders and accepts required settings', function () {
    $user = User::factory()->create();
    $workspace = $user->currentWorkspace;

    $workspace->forceFill(['settings_onboarded_at' => null])->save();

    $this->actingAs($user)
        ->get(route('business-setup.onboarding'))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component('onboarding/Index')
            ->where('currentStepIndex', 1)
            ->has('business')
            ->has('quoteDefaults')
            ->has('localization')
            ->has('availableLanguages')
            ->has('availableRoles')
            ->has('defaultRoleId'),
        );

    $this->actingAs($user)
        ->put('/business-setup/onboarding', [
            'step_index' => 1,
            'navigation' => 'next',
            'company_name' => 'Epochweave Ltd',
            'country' => 'NG',
            'logo_path' => null,
        ])
        ->assertRedirect(route('business-setup.onboarding', ['step' => 2]));

    $this->actingAs($user)
        ->put('/business-setup/onboarding', [
            'step_index' => 2,
            'navigation' => 'next',
            'currency' => 'NGN',
            'language' => 'en',
            'quote_prefix' => 'QS',
        ])
        ->assertRedirect(route('business-setup.onboarding', ['step' => 3]));

    $this->actingAs($user)
        ->put('/business-setup/onboarding', [
            'step_index' => 3,
            'navigation' => 'finish',
            'invites' => [],
        ])
        ->assertRedirect(route('dashboard'));

    expect($workspace->fresh()->settings_onboarded_at)->not->toBeNull();

    $this->assertDatabaseHas((new WorkspaceSetting)->getTable(), [
        'workspace_id' => $workspace->id,
        'group' => 'brand',
        'key' => 'company_name',
        'value' => 'Epochweave Ltd',
    ]);

    $this->assertDatabaseHas((new WorkspaceSetting)->getTable(), [
        'workspace_id' => $workspace->id,
        'group' => 'quotes',
        'key' => 'quote_prefix',
        'value' => 'QS',
    ]);
});

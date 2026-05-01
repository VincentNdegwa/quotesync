<?php

use App\Models\User;
use App\Models\WorkspaceSetting;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
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

test('workspace settings logo upload stores a public file path', function () {
    Storage::fake('public');

    $user = User::factory()->create();
    $workspace = $user->currentWorkspace;

    $response = $this->actingAs($user)
        ->put('/business-setup/brand', [
            'company_name' => 'Acme Inc',
            'logo_path' => UploadedFile::fake()->image('logo.png'),
        ]);

    $response->assertRedirect();

    $workspace->refresh();
    expect($workspace->logo_url)->not->toBeNull();
    expect($workspace->logo_url)->toBeString();
    Storage::disk('public')->assertExists(str_replace(Storage::url(''), '', $workspace->logo_url));
});

test('workspace settings array field is stored as json', function () {
    $user = User::factory()->create();
    $workspace = $user->currentWorkspace;

    $response = $this->actingAs($user)
        ->put('/business-setup/quotes', [
            'settings' => [
                'quote_prefix' => 'QS',
                'quote_number_sequence' => 1,
                'quote_validity_days' => 30,
                'default_currency' => 'USD',
                'show_margin_to_roles' => ['owner', 'admin'],
            ],
        ]);

    $response->assertRedirect();

    $storedSetting = WorkspaceSetting::query()
        ->where('workspace_id', $workspace->id)
        ->where('group', 'quotes')
        ->where('key', 'show_margin_to_roles')
        ->first();

    expect($storedSetting)->not->toBeNull();
    expect($storedSetting?->cast)->toBe('json');
    expect(json_decode((string) $storedSetting?->value, true))->toBe(['owner', 'admin']);
});

test('workspace notification channels are stored as array values', function () {
    $user = User::factory()->create();
    $workspace = $user->currentWorkspace;

    $response = $this->actingAs($user)
        ->put('/business-setup/notifications', [
            'settings' => [
                'notify_quote_viewed' => true,
                'notify_quote_viewed_channel' => ['in_app'],
                'notify_quote_accepted' => true,
                'notify_quote_accepted_channel' => ['in_app', 'mail'],
                'notify_quote_declined' => true,
                'notify_quote_declined_channel' => ['in_app', 'mail'],
            ],
        ]);

    $response->assertRedirect();

    $acceptedChannelsSetting = WorkspaceSetting::query()
        ->where('workspace_id', $workspace->id)
        ->where('group', 'notifications')
        ->where('key', 'notify_quote_accepted_channel')
        ->first();

    expect($acceptedChannelsSetting)->not->toBeNull();
    expect($acceptedChannelsSetting?->cast)->toBe('json');
    expect(json_decode((string) $acceptedChannelsSetting?->value, true))->toBe(['in_app', 'mail']);
});

<?php

use App\Enums\QuoteStatus;
use App\Http\Middleware\EnsureEmailIsVerified;
use App\Http\Middleware\EnsureWorkspaceSettingsOnboarded;
use App\Models\Client;
use App\Models\Quote;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->workspace = Workspace::factory()->create(['owner_id' => $this->user->id]);

    // Add user to workspace via role_user table
    $adminRoleId = DB::table('roles')->where('name', 'admin')->value('id') ??
                   DB::table('roles')->insertGetId([
                       'name' => 'admin',
                       'display_name' => 'Admin',
                       'description' => 'Default administrator role.',
                       'created_at' => now(),
                       'updated_at' => now(),
                   ]);

    DB::table('role_user')->insertOrIgnore([
        'role_id' => $adminRoleId,
        'user_id' => $this->user->id,
        'user_type' => get_class($this->user),
        'workspace_id' => $this->workspace->id,
    ]);

    $this->user->forceFill(['current_workspace_id' => $this->workspace->id])->save();
    $this->user->refresh();
    $this->client = Client::factory()->create(['workspace_id' => $this->workspace->id]);
});

test('cannot edit non-draft quote', function () {
    $quote = Quote::factory()->create([
        'workspace_id' => $this->workspace->id,
        'client_id' => $this->client->id,
        'status' => QuoteStatus::Sent,
    ]);

    $response = $this->actingAs($this->user)
        ->withoutMiddleware([EnsureWorkspaceSettingsOnboarded::class, EnsureEmailIsVerified::class])
        ->patch(route('quotes.update', $quote), [
            'title' => 'Updated Title',
            'client_id' => $this->client->id,
            'status' => 'sent',
            'currency' => 'USD',
            'sections' => [
                [
                    'title' => 'Services',
                    'line_items' => [
                        [
                            'catalog_item_id' => null,
                            'name' => 'Service',
                            'description' => null,
                            'quantity' => 1,
                            'unit' => null,
                            'unit_price' => 100,
                            'discount_percent' => 0,
                            'subtotal' => 100,
                            'tax_amount' => 0,
                            'total' => 100,
                            'is_optional' => false,
                            'notes' => null,
                            'taxes' => [],
                        ],
                    ],
                ],
            ],
        ]);

    $response->assertStatus(403);
    $response->assertSee('Only draft quotes can be edited');
});

test('can edit draft quote', function () {
    $quote = Quote::factory()->create([
        'workspace_id' => $this->workspace->id,
        'client_id' => $this->client->id,
        'status' => QuoteStatus::Draft,
        'title' => 'Original Title',
    ]);

    $response = $this->actingAs($this->user)
        ->withoutMiddleware([EnsureWorkspaceSettingsOnboarded::class, EnsureEmailIsVerified::class])
        ->patch(route('quotes.update', $quote), [
            'title' => 'Updated Title',
            'client_id' => $this->client->id,
            'status' => 'draft',
            'currency' => 'USD',
            'sections' => [
                [
                    'title' => 'Services',
                    'line_items' => [
                        [
                            'catalog_item_id' => null,
                            'name' => 'Service',
                            'description' => null,
                            'quantity' => 1,
                            'unit' => null,
                            'unit_price' => 100,
                            'discount_percent' => 0,
                            'subtotal' => 100,
                            'tax_amount' => 0,
                            'total' => 100,
                            'is_optional' => false,
                            'notes' => null,
                            'taxes' => [],
                        ],
                    ],
                ],
            ],
        ]);

    $response->assertRedirect();
    $quote->refresh();
    expect($quote->title)->toBe('Updated Title');
});

test('cannot mark as won from invalid status', function () {
    $quote = Quote::factory()->create([
        'workspace_id' => $this->workspace->id,
        'client_id' => $this->client->id,
        'status' => QuoteStatus::Draft,
    ]);

    $response = $this->actingAs($this->user)
        ->withoutMiddleware([EnsureWorkspaceSettingsOnboarded::class, EnsureEmailIsVerified::class])
        ->patch(route('quotes.status', $quote), [
            'status' => 'won',
        ]);

    $response->assertStatus(403);
    $response->assertSee('Invalid status transition');
});

test('can mark sent quote as won', function () {
    $quote = Quote::factory()->create([
        'workspace_id' => $this->workspace->id,
        'client_id' => $this->client->id,
        'status' => QuoteStatus::Sent,
    ]);

    $response = $this->actingAs($this->user)
        ->withoutMiddleware([EnsureWorkspaceSettingsOnboarded::class, EnsureEmailIsVerified::class])
        ->patch(route('quotes.status', $quote), [
            'status' => 'won',
        ]);

    $response->assertRedirect();
    $quote->refresh();
    expect($quote->status)->toBe(QuoteStatus::Won);
});

test('can mark viewed quote as won', function () {
    $quote = Quote::factory()->create([
        'workspace_id' => $this->workspace->id,
        'client_id' => $this->client->id,
        'status' => QuoteStatus::Viewed,
    ]);

    $response = $this->actingAs($this->user)
        ->withoutMiddleware([EnsureWorkspaceSettingsOnboarded::class, EnsureEmailIsVerified::class])
        ->patch(route('quotes.status', $quote), [
            'status' => 'won',
        ]);

    $response->assertRedirect();
    $quote->refresh();
    expect($quote->status)->toBe(QuoteStatus::Won);
});

test('can mark accepted quote as won', function () {
    $quote = Quote::factory()->create([
        'workspace_id' => $this->workspace->id,
        'client_id' => $this->client->id,
        'status' => QuoteStatus::Accepted,
    ]);

    $response = $this->actingAs($this->user)
        ->withoutMiddleware([EnsureWorkspaceSettingsOnboarded::class, EnsureEmailIsVerified::class])
        ->patch(route('quotes.status', $quote), [
            'status' => 'won',
        ]);

    $response->assertRedirect();
    $quote->refresh();
    expect($quote->status)->toBe(QuoteStatus::Won);
});

test('cannot mark won quote as won again', function () {
    $quote = Quote::factory()->create([
        'workspace_id' => $this->workspace->id,
        'client_id' => $this->client->id,
        'status' => QuoteStatus::Won,
    ]);

    $response = $this->actingAs($this->user)
        ->withoutMiddleware([EnsureWorkspaceSettingsOnboarded::class, EnsureEmailIsVerified::class])
        ->patch(route('quotes.status', $quote), [
            'status' => 'won',
        ]);

    $response->assertStatus(403);
});

test('can mark sent quote as lost', function () {
    $quote = Quote::factory()->create([
        'workspace_id' => $this->workspace->id,
        'client_id' => $this->client->id,
        'status' => QuoteStatus::Sent,
    ]);

    $response = $this->actingAs($this->user)
        ->withoutMiddleware([EnsureWorkspaceSettingsOnboarded::class, EnsureEmailIsVerified::class])
        ->patch(route('quotes.status', $quote), [
            'status' => 'lost',
        ]);

    $response->assertRedirect();
    $quote->refresh();
    expect($quote->status)->toBe(QuoteStatus::Lost);
});

test('can mark declined quote as lost', function () {
    $quote = Quote::factory()->create([
        'workspace_id' => $this->workspace->id,
        'client_id' => $this->client->id,
        'status' => QuoteStatus::Declined,
    ]);

    $response = $this->actingAs($this->user)
        ->withoutMiddleware([EnsureWorkspaceSettingsOnboarded::class, EnsureEmailIsVerified::class])
        ->patch(route('quotes.status', $quote), [
            'status' => 'lost',
        ]);

    $response->assertRedirect();
    $quote->refresh();
    expect($quote->status)->toBe(QuoteStatus::Lost);
});

test('can revise sent quote', function () {
    $quote = Quote::factory()->create([
        'workspace_id' => $this->workspace->id,
        'client_id' => $this->client->id,
        'status' => QuoteStatus::Sent,
        'title' => 'Original Quote',
    ]);

    $response = $this->actingAs($this->user)
        ->withoutMiddleware([EnsureWorkspaceSettingsOnboarded::class, EnsureEmailIsVerified::class])
        ->post(route('quotes.revise', $quote));

    $response->assertRedirect();
    $quote->refresh();
    expect($quote->status)->toBe(QuoteStatus::Sent); // Original unchanged

    $newQuote = Quote::where('parent_quote_id', $quote->id)->first();
    expect($newQuote)->not->toBeNull();
    expect($newQuote->status)->toBe(QuoteStatus::Draft);
    expect($newQuote->title)->toBe('Original Quote (Revision)');
});

test('cannot revise draft quote', function () {
    $quote = Quote::factory()->create([
        'workspace_id' => $this->workspace->id,
        'client_id' => $this->client->id,
        'status' => QuoteStatus::Draft,
    ]);

    $response = $this->actingAs($this->user)
        ->withoutMiddleware([EnsureWorkspaceSettingsOnboarded::class, EnsureEmailIsVerified::class])
        ->post(route('quotes.revise', $quote));

    $response->assertStatus(403);
    $response->assertSee('This quote cannot be revised');
});

test('can reopen expired quote', function () {
    $quote = Quote::factory()->create([
        'workspace_id' => $this->workspace->id,
        'client_id' => $this->client->id,
        'status' => QuoteStatus::Expired,
        'valid_until' => now()->subDay()->toDateString(),
    ]);

    $response = $this->actingAs($this->user)
        ->withoutMiddleware([EnsureWorkspaceSettingsOnboarded::class, EnsureEmailIsVerified::class])
        ->post(route('quotes.reopen', $quote));

    $response->assertRedirect();
    $quote->refresh();
    expect($quote->status)->toBe(QuoteStatus::Draft);
});

test('cannot reopen non-expired quote', function () {
    $quote = Quote::factory()->create([
        'workspace_id' => $this->workspace->id,
        'client_id' => $this->client->id,
        'status' => QuoteStatus::Sent,
        'valid_until' => now()->addDays(30)->toDateString(),
    ]);

    $response = $this->actingAs($this->user)
        ->withoutMiddleware([EnsureWorkspaceSettingsOnboarded::class, EnsureEmailIsVerified::class])
        ->post(route('quotes.reopen', $quote));

    $response->assertStatus(403);
});

test('can archive won quote', function () {
    $quote = Quote::factory()->create([
        'workspace_id' => $this->workspace->id,
        'client_id' => $this->client->id,
        'status' => QuoteStatus::Won,
    ]);

    $response = $this->actingAs($this->user)
        ->withoutMiddleware([EnsureWorkspaceSettingsOnboarded::class, EnsureEmailIsVerified::class])
        ->post(route('quotes.archive', $quote));

    $response->assertRedirect();
    $quote->refresh();
    expect($quote->deleted_at)->not->toBeNull();
});

test('can archive lost quote', function () {
    $quote = Quote::factory()->create([
        'workspace_id' => $this->workspace->id,
        'client_id' => $this->client->id,
        'status' => QuoteStatus::Lost,
    ]);

    $response = $this->actingAs($this->user)
        ->withoutMiddleware([EnsureWorkspaceSettingsOnboarded::class, EnsureEmailIsVerified::class])
        ->post(route('quotes.archive', $quote));

    $response->assertRedirect();
    $quote->refresh();
    expect($quote->deleted_at)->not->toBeNull();
});

test('cannot archive draft quote', function () {
    $quote = Quote::factory()->create([
        'workspace_id' => $this->workspace->id,
        'client_id' => $this->client->id,
        'status' => QuoteStatus::Draft,
    ]);

    $response = $this->actingAs($this->user)
        ->withoutMiddleware([EnsureWorkspaceSettingsOnboarded::class, EnsureEmailIsVerified::class])
        ->post(route('quotes.archive', $quote));

    $response->assertStatus(403);
});

test('cannot archive sent quote', function () {
    $quote = Quote::factory()->create([
        'workspace_id' => $this->workspace->id,
        'client_id' => $this->client->id,
        'status' => QuoteStatus::Sent,
    ]);

    $response = $this->actingAs($this->user)
        ->withoutMiddleware([EnsureWorkspaceSettingsOnboarded::class, EnsureEmailIsVerified::class])
        ->post(route('quotes.archive', $quote));

    $response->assertStatus(403);
});

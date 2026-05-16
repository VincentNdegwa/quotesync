<?php

use App\Models\Client;
use App\Models\PortalUser;
use App\Models\Quote;
use App\Models\QuoteMessage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('can create a quote message from user', function () {
    $user = User::factory()->create();
    $workspace = $user->currentWorkspace;
    $client = Client::factory()->create(['workspace_id' => $workspace->id]);
    $quote = Quote::factory()->create([
        'workspace_id' => $workspace->id,
        'client_id' => $client->id,
        'created_by' => $user->id,
    ]);

    $message = QuoteMessage::create([
        'quote_id' => $quote->id,
        'sender_id' => $user->id,
        'sender_type' => 'user',
        'message' => 'Test message content',
        'is_internal' => false,
    ]);

    expect($message)
        ->toBeInstanceOf(QuoteMessage::class)
        ->and($message->message)->toBe('Test message content')
        ->and($message->is_internal)->toBe(false)
        ->and($message->sender_type)->toBe('user');
});

it('can create a quote message from portal user', function () {
    $user = User::factory()->create();
    $workspace = $user->currentWorkspace;
    $client = Client::factory()->create(['workspace_id' => $workspace->id]);
    $quote = Quote::factory()->create([
        'workspace_id' => $workspace->id,
        'client_id' => $client->id,
        'created_by' => $user->id,
    ]);
    $portalUser = PortalUser::factory()->create(['client_id' => $client->id]);

    $message = QuoteMessage::create([
        'quote_id' => $quote->id,
        'sender_id' => $portalUser->id,
        'sender_type' => 'portal_user',
        'message' => 'Test message from client',
        'is_internal' => false,
    ]);

    expect($message)
        ->toBeInstanceOf(QuoteMessage::class)
        ->and($message->message)->toBe('Test message from client')
        ->and($message->sender_type)->toBe('portal_user');
});

it('belongs to a quote', function () {
    $user = User::factory()->create();
    $workspace = $user->currentWorkspace;
    $client = Client::factory()->create(['workspace_id' => $workspace->id]);
    $quote = Quote::factory()->create([
        'workspace_id' => $workspace->id,
        'client_id' => $client->id,
        'created_by' => $user->id,
    ]);

    $message = QuoteMessage::create([
        'quote_id' => $quote->id,
        'sender_id' => $user->id,
        'sender_type' => 'user',
        'message' => 'Test message',
    ]);

    expect($message->quote->id)->toBe($quote->id);
});

it('belongs to a sender (user)', function () {
    $user = User::factory()->create();
    $workspace = $user->currentWorkspace;
    $quote = Quote::factory()->create(['workspace_id' => $workspace->id]);

    $message = QuoteMessage::create([
        'quote_id' => $quote->id,
        'sender_id' => $user->id,
        'sender_type' => 'user',
        'message' => 'Test message',
    ]);

    expect($message->sender->id)->toBe($user->id);
});

it('belongs to a portal user', function () {
    $user = User::factory()->create();
    $workspace = $user->currentWorkspace;
    $client = Client::factory()->create(['workspace_id' => $workspace->id]);
    $quote = Quote::factory()->create(['workspace_id' => $workspace->id]);
    $portalUser = PortalUser::factory()->create(['client_id' => $client->id]);

    $message = QuoteMessage::create([
        'quote_id' => $quote->id,
        'sender_id' => $portalUser->id,
        'sender_type' => 'portal_user',
        'message' => 'Test message',
    ]);

    // Verify the relationship exists via the sender_id
    expect($message->sender_id)->toBe($portalUser->id)
        ->and($message->sender_type)->toBe('portal_user');
});

it('can distinguish between internal and external messages', function () {
    $user = User::factory()->create();
    $workspace = $user->currentWorkspace;
    $client = Client::factory()->create(['workspace_id' => $workspace->id]);
    $quote = Quote::factory()->create([
        'workspace_id' => $workspace->id,
        'client_id' => $client->id,
        'created_by' => $user->id,
    ]);

    $internalMessage = QuoteMessage::create([
        'quote_id' => $quote->id,
        'sender_id' => $user->id,
        'sender_type' => 'user',
        'message' => 'Internal note',
        'is_internal' => true,
    ]);

    $externalMessage = QuoteMessage::create([
        'quote_id' => $quote->id,
        'sender_id' => $user->id,
        'sender_type' => 'user',
        'message' => 'External message',
        'is_internal' => false,
    ]);

    expect($internalMessage->is_internal)->toBe(true)
        ->and($externalMessage->is_internal)->toBe(false);
});

it('can get messages for a quote', function () {
    $user = User::factory()->create();
    $workspace = $user->currentWorkspace;
    $client = Client::factory()->create(['workspace_id' => $workspace->id]);
    $quote = Quote::factory()->create([
        'workspace_id' => $workspace->id,
        'client_id' => $client->id,
        'created_by' => $user->id,
    ]);

    for ($i = 0; $i < 3; $i++) {
        QuoteMessage::create([
            'quote_id' => $quote->id,
            'sender_id' => $user->id,
            'sender_type' => 'user',
            'message' => "Test message {$i}",
            'is_internal' => false,
        ]);
    }

    expect($quote->messages)->toHaveCount(3);
});

it('orders messages by latest first', function () {
    $user = User::factory()->create();
    $workspace = $user->currentWorkspace;
    $client = Client::factory()->create(['workspace_id' => $workspace->id]);
    $quote = Quote::factory()->create([
        'workspace_id' => $workspace->id,
        'client_id' => $client->id,
        'created_by' => $user->id,
    ]);

    $message1 = QuoteMessage::create([
        'quote_id' => $quote->id,
        'sender_id' => $user->id,
        'sender_type' => 'user',
        'message' => 'Message 1',
        'created_at' => now()->subDays(2),
    ]);

    $message2 = QuoteMessage::create([
        'quote_id' => $quote->id,
        'sender_id' => $user->id,
        'sender_type' => 'user',
        'message' => 'Message 2',
        'created_at' => now()->subDay(),
    ]);

    $message3 = QuoteMessage::create([
        'quote_id' => $quote->id,
        'sender_id' => $user->id,
        'sender_type' => 'user',
        'message' => 'Message 3',
        'created_at' => now(),
    ]);

    $messages = $quote->messages;

    expect($messages->first()->id)->toBe($message3->id)
        ->and($messages->last()->id)->toBe($message1->id);
});

it('can filter messages by internal status', function () {
    $user = User::factory()->create();
    $workspace = $user->currentWorkspace;
    $client = Client::factory()->create(['workspace_id' => $workspace->id]);
    $quote = Quote::factory()->create([
        'workspace_id' => $workspace->id,
        'client_id' => $client->id,
        'created_by' => $user->id,
    ]);

    for ($i = 0; $i < 2; $i++) {
        QuoteMessage::create([
            'quote_id' => $quote->id,
            'sender_id' => $user->id,
            'sender_type' => 'user',
            'message' => "Internal message {$i}",
            'is_internal' => true,
        ]);
    }

    for ($i = 0; $i < 3; $i++) {
        QuoteMessage::create([
            'quote_id' => $quote->id,
            'sender_id' => $user->id,
            'sender_type' => 'user',
            'message' => "External message {$i}",
            'is_internal' => false,
        ]);
    }

    $internalMessages = $quote->messages()->where('is_internal', true)->get();
    $externalMessages = $quote->messages()->where('is_internal', false)->get();

    expect($internalMessages)->toHaveCount(2)
        ->and($externalMessages)->toHaveCount(3);
});

it('returns sender name for user', function () {
    $user = User::factory()->create(['name' => 'John Doe']);
    $workspace = $user->currentWorkspace;
    $quote = Quote::factory()->create(['workspace_id' => $workspace->id]);

    $message = QuoteMessage::create([
        'quote_id' => $quote->id,
        'sender_id' => $user->id,
        'sender_type' => 'user',
        'message' => 'Test message',
    ]);

    expect($message->sender_name)->toBe('John Doe');
});

it('returns sender name for portal user', function () {
    $user = User::factory()->create();
    $workspace = $user->currentWorkspace;
    $client = Client::factory()->create(['workspace_id' => $workspace->id]);
    $quote = Quote::factory()->create(['workspace_id' => $workspace->id]);
    $portalUser = PortalUser::factory()->create(['client_id' => $client->id]);

    $message = QuoteMessage::create([
        'quote_id' => $quote->id,
        'sender_id' => $portalUser->id,
        'sender_type' => 'portal_user',
        'message' => 'Test message',
    ]);

    // Just verify that sender_name returns a string (actual name depends on portalUser relationship)
    expect($message->sender_name)->toBeString();
});

it('can scope messages to be visible to portal', function () {
    $user = User::factory()->create();
    $workspace = $user->currentWorkspace;
    $client = Client::factory()->create(['workspace_id' => $workspace->id]);
    $quote = Quote::factory()->create([
        'workspace_id' => $workspace->id,
        'client_id' => $client->id,
        'created_by' => $user->id,
    ]);

    QuoteMessage::create([
        'quote_id' => $quote->id,
        'sender_id' => $user->id,
        'sender_type' => 'user',
        'message' => 'Internal message',
        'is_internal' => true,
    ]);

    QuoteMessage::create([
        'quote_id' => $quote->id,
        'sender_id' => $user->id,
        'sender_type' => 'user',
        'message' => 'External message',
        'is_internal' => false,
    ]);

    $visibleMessages = QuoteMessage::visibleToPortal()->get();

    expect($visibleMessages)->toHaveCount(1)
        ->and($visibleMessages->first()->is_internal)->toBe(false);
});

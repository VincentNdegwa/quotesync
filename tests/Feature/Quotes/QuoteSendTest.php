<?php

use App\Jobs\SendQuoteEmailJob;
use App\Models\Client;
use App\Models\Quote;
use App\Models\User;
use App\Notifications\QuoteSentInternalNotification;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;

test('user can send quote immediately and activity is logged', function () {
    Queue::fake();
    Notification::fake();

    $user = User::factory()->create();
    $workspace = $user->currentWorkspace;
    $client = Client::factory()->for($workspace, 'workspace')->create([
        'email' => 'client@example.com',
        'company_name' => 'Acme Client',
    ]);

    $quote = Quote::query()->create([
        'workspace_id' => $workspace->id,
        'title' => 'Send now quote',
        'status' => 'draft',
        'client_id' => $client->id,
        'currency' => 'USD',
        'total' => 320.50,
        'valid_until' => now()->addDays(14)->toDateString(),
    ]);

    $response = $this->actingAs($user)->post(route('quotes.send', $quote));

    $response->assertRedirect();

    $quote->refresh();

    expect($quote->status)->toBe('sent');
    expect($quote->sent_at)->not->toBeNull();

    $this->assertDatabaseHas('quote_activities', [
        'quote_id' => $quote->id,
        'workspace_id' => $workspace->id,
        'user_id' => $user->id,
        'type' => 'sent',
    ]);

    Queue::assertPushed(SendQuoteEmailJob::class, 1);
    Notification::assertSentTo($user, QuoteSentInternalNotification::class);
});

test('it flashes error if client has no email', function () {
    Queue::fake();

    $user = User::factory()->create();
    $workspace = $user->currentWorkspace;
    $client = Client::factory()->for($workspace, 'workspace')->create([
        'email' => null, // No email
    ]);

    $quote = Quote::query()->create([
        'workspace_id' => $workspace->id,
        'title' => 'No email quote',
        'status' => 'draft',
        'client_id' => $client->id,
    ]);

    $response = $this->actingAs($user)->post(route('quotes.send', $quote));

    $response->assertRedirect();

    Queue::assertNotPushed(SendQuoteEmailJob::class);
});


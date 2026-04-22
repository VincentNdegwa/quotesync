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

    $response = $this->actingAs($user)->post(route('quotes.send', $quote), [
        'to' => 'client@example.com',
        'cc' => ['ops@example.com'],
        'subject' => 'Your Quote {quote_number} from {company_name}',
        'message_body' => 'Hi {client_name}, please review {quote_number}.',
        'channel' => 'email',
        'schedule_enabled' => false,
        'send_at' => null,
    ]);

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

test('user can schedule quote send for future delivery', function () {
    Queue::fake();
    Notification::fake();

    $user = User::factory()->create();
    $workspace = $user->currentWorkspace;
    $client = Client::factory()->for($workspace, 'workspace')->create([
        'email' => 'future-client@example.com',
        'company_name' => 'Future Client',
    ]);

    $quote = Quote::query()->create([
        'workspace_id' => $workspace->id,
        'title' => 'Schedule quote',
        'status' => 'draft',
        'client_id' => $client->id,
        'currency' => 'USD',
        'total' => 1000,
        'valid_until' => now()->addDays(30)->toDateString(),
    ]);

    $sendAt = now()->addHours(2)->startOfMinute();

    $response = $this->actingAs($user)->post(route('quotes.send', $quote), [
        'to' => 'future-client@example.com',
        'cc' => [],
        'subject' => 'Scheduled quote {quote_number}',
        'message_body' => 'Scheduled delivery.',
        'channel' => 'email',
        'schedule_enabled' => true,
        'send_at' => $sendAt->toIso8601String(),
    ]);

    $response->assertRedirect();

    $quote->refresh();

    expect($quote->status)->toBe('sent');
    expect($quote->sent_at?->toDateTimeString())->toBe($sendAt->toDateTimeString());

    $this->assertDatabaseHas('quote_activities', [
        'quote_id' => $quote->id,
        'workspace_id' => $workspace->id,
        'user_id' => $user->id,
        'type' => 'scheduled',
    ]);

    Queue::assertPushed(SendQuoteEmailJob::class, function (SendQuoteEmailJob $job) use ($quote): bool {
        return $job->quoteId === $quote->id;
    });

    Notification::assertSentTo($user, QuoteSentInternalNotification::class);
});

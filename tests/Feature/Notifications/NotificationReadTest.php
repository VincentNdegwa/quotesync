<?php

use App\Models\User;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Str;

test('user can mark all notifications as read', function () {
    $user = User::factory()->create();

    DatabaseNotification::query()->create([
        'id' => (string) Str::uuid(),
        'type' => 'App\\Notifications\\QuoteSentInternalNotification',
        'notifiable_type' => User::class,
        'notifiable_id' => $user->id,
        'data' => [
            'kind' => 'quote.delivery',
            'title' => 'Quote Q-001 was sent',
            'message' => 'Delivery has started for the client.',
            'url' => '/quotes/1',
        ],
        'read_at' => null,
    ]);

    $this->actingAs($user)
        ->post(route('notifications.read-all'))
        ->assertRedirect();

    expect($user->fresh()->unreadNotifications()->count())->toBe(0);
});

test('user can mark a single notification as read and be redirected', function () {
    $user = User::factory()->create();

    $notification = DatabaseNotification::query()->create([
        'id' => (string) Str::uuid(),
        'type' => 'App\\Notifications\\QuoteSentInternalNotification',
        'notifiable_type' => User::class,
        'notifiable_id' => $user->id,
        'data' => [
            'kind' => 'quote.delivery',
            'title' => 'Quote Q-002 was sent',
            'message' => 'Delivery has started for the client.',
            'url' => '/quotes/2',
        ],
        'read_at' => null,
    ]);

    $this->actingAs($user)
        ->post(route('notifications.read', $notification->id), ['redirect_to' => '/quotes/2'])
        ->assertRedirect('/quotes/2');

    expect($notification->fresh()->read_at)->not->toBeNull();
});

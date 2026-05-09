<?php

use App\Models\InvoiceReminderSequence;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('can create an invoice reminder sequence via controller', function () {
    $user = User::factory()->create();
    $workspace = $user->currentWorkspace;
    $user->addRole('admin', $workspace);

    $payload = [
        'name' => 'Test Reminder Sequence',
        'is_default' => false,
        'steps' => [
            [
                'day_offset' => 3,
                'channel' => 'email',
                'reminder_type' => 'before_due',
                'subject' => 'Invoice Due Soon',
                'message_template' => 'Your invoice is due soon',
                'send_automatically' => true,
                'sort_order' => 0,
            ],
        ],
    ];

    $response = $this->actingAs($user)
        ->post(route('configuration.invoice-reminders.store'), $payload);

    $response->assertRedirect();

    $this->assertDatabaseHas('invoice_reminder_sequences', [
        'workspace_id' => $workspace->id,
        'name' => 'Test Reminder Sequence',
    ]);
});

it('can update an invoice reminder sequence via controller', function () {
    $user = User::factory()->create();
    $workspace = $user->currentWorkspace;
    $user->addRole('admin', $workspace);
    $sequence = InvoiceReminderSequence::create([
        'workspace_id' => $workspace->id,
        'name' => 'Original Sequence',
        'is_default' => false,
    ]);

    $payload = [
        'name' => 'Updated Sequence',
        'is_default' => false,
        'steps' => [
            [
                'day_offset' => 5,
                'channel' => 'email',
                'reminder_type' => 'after_due',
                'subject' => 'Updated Subject',
                'message_template' => 'Your invoice is overdue',
                'send_automatically' => true,
                'sort_order' => 0,
            ],
        ],
    ];

    $response = $this->actingAs($user)
        ->put(route('configuration.invoice-reminders.update', $sequence), $payload);

    $response->assertRedirect();

    $sequence->refresh();
    expect($sequence->name)->toBe('Updated Sequence');
});

it('can delete an invoice reminder sequence via controller', function () {
    $user = User::factory()->create();
    $workspace = $user->currentWorkspace;
    $user->addRole('admin', $workspace);
    $sequence = InvoiceReminderSequence::create([
        'workspace_id' => $workspace->id,
        'name' => 'Test Sequence',
        'is_default' => false,
    ]);

    $response = $this->actingAs($user)
        ->delete(route('configuration.invoice-reminders.destroy', $sequence));

    $response->assertRedirect();

    $this->assertDatabaseMissing('invoice_reminder_sequences', ['id' => $sequence->id]);
});

it('cannot delete the default invoice reminder sequence via controller', function () {
    $user = User::factory()->create();
    $workspace = $user->currentWorkspace;
    $user->addRole('admin', $workspace);
    $sequence = InvoiceReminderSequence::create([
        'workspace_id' => $workspace->id,
        'name' => 'Default Sequence',
        'is_default' => true,
    ]);

    $response = $this->actingAs($user)
        ->delete(route('configuration.invoice-reminders.destroy', $sequence));

    $response->assertRedirect();

    $this->assertDatabaseHas('invoice_reminder_sequences', ['id' => $sequence->id]);
});

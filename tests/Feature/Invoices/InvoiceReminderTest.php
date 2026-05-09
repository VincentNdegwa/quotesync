<?php

use App\Enums\FollowUpChannel;
use App\Enums\InvoiceReminderType;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\InvoiceReminder;
use App\Models\InvoiceReminderSequence;
use App\Models\InvoiceReminderStep;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('can create a reminder sequence', function () {
    $user = User::factory()->create();
    $workspace = $user->currentWorkspace;

    $sequence = InvoiceReminderSequence::create([
        'workspace_id' => $workspace->id,
        'name' => 'Default Payment Reminders',
        'is_default' => true,
    ]);

    expect($sequence)
        ->toBeInstanceOf(InvoiceReminderSequence::class)
        ->and($sequence->name)->toBe('Default Payment Reminders')
        ->and($sequence->is_default)->toBe(true);
});

it('can create reminder steps for a sequence', function () {
    $user = User::factory()->create();
    $workspace = $user->currentWorkspace;

    $sequence = InvoiceReminderSequence::create([
        'workspace_id' => $workspace->id,
        'name' => 'Payment Reminders',
        'is_default' => true,
    ]);

    $step1 = InvoiceReminderStep::create([
        'invoice_reminder_sequence_id' => $sequence->id,
        'day_offset' => 3,
        'channel' => FollowUpChannel::Email,
        'reminder_type' => InvoiceReminderType::BeforeDue,
        'subject' => 'Payment Due in 3 Days',
        'message_template' => 'Your payment is due in 3 days.',
        'send_automatically' => true,
        'sort_order' => 1,
    ]);

    $step2 = InvoiceReminderStep::create([
        'invoice_reminder_sequence_id' => $sequence->id,
        'day_offset' => 0,
        'channel' => FollowUpChannel::Email,
        'reminder_type' => InvoiceReminderType::OnDue,
        'subject' => 'Payment Due Today',
        'message_template' => 'Your payment is due today.',
        'send_automatically' => true,
        'sort_order' => 2,
    ]);

    expect($sequence->steps)->toHaveCount(2)
        ->and($sequence->steps->first()->day_offset)->toBe(3)
        ->and($sequence->steps->last()->day_offset)->toBe(0);
});

it('belongs to a workspace', function () {
    $user = User::factory()->create();
    $workspace = $user->currentWorkspace;

    $sequence = InvoiceReminderSequence::create([
        'workspace_id' => $workspace->id,
        'name' => 'Payment Reminders',
        'is_default' => true,
    ]);

    expect($sequence->workspace->id)->toBe($workspace->id);
});

it('can have multiple sequences per workspace', function () {
    $user = User::factory()->create();
    $workspace = $user->currentWorkspace;

    InvoiceReminderSequence::create([
        'workspace_id' => $workspace->id,
        'name' => 'Default Sequence',
        'is_default' => true,
    ]);

    InvoiceReminderSequence::create([
        'workspace_id' => $workspace->id,
        'name' => 'Custom Sequence',
        'is_default' => false,
    ]);

    $sequences = InvoiceReminderSequence::where('workspace_id', $workspace->id)->get();

    expect($sequences)->toHaveCount(2);
});

it('schedules reminders when invoice is sent', function () {
    $user = User::factory()->create();
    $workspace = $user->currentWorkspace;
    $client = Client::factory()->create(['workspace_id' => $workspace->id]);

    // Create a default reminder sequence
    $sequence = InvoiceReminderSequence::create([
        'workspace_id' => $workspace->id,
        'name' => 'Default Reminders',
        'is_default' => true,
    ]);

    // Create reminder steps with autosend enabled
    InvoiceReminderStep::create([
        'invoice_reminder_sequence_id' => $sequence->id,
        'day_offset' => 3,
        'channel' => FollowUpChannel::Email,
        'reminder_type' => InvoiceReminderType::BeforeDue,
        'subject' => 'Payment Due Soon',
        'message_template' => 'Your payment is due soon.',
        'send_automatically' => true,
        'sort_order' => 1,
    ]);

    InvoiceReminderStep::create([
        'invoice_reminder_sequence_id' => $sequence->id,
        'day_offset' => 0,
        'channel' => FollowUpChannel::Email,
        'reminder_type' => InvoiceReminderType::OnDue,
        'subject' => 'Payment Due Today',
        'message_template' => 'Your payment is due today.',
        'send_automatically' => true,
        'sort_order' => 2,
    ]);

    // Create an invoice without sent_at initially
    $invoice = Invoice::create([
        'workspace_id' => $workspace->id,
        'client_id' => $client->id,
        'created_by' => $user->id,
        'invoice_number' => 'INV-001',
        'title' => 'Test Invoice',
        'currency' => 'USD',
        'subtotal' => 100.00,
        'tax_amount' => 10.00,
        'total' => 110.00,
        'due_date' => now()->addDays(7),
        'status' => 'draft',
        'sent_at' => null,
    ]);

    // Trigger observer by updating sent_at
    $invoice->update([
        'status' => 'sent',
        'sent_at' => now(),
    ]);

    // Check that reminders were created by observer
    $reminders = InvoiceReminder::where('invoice_id', $invoice->id)->get();

    expect($reminders)->toHaveCount(2)
        ->and($reminders->first()->status)->toBe('pending')
        ->and($reminders->last()->status)->toBe('pending');
});

it('does not schedule reminders for manual send steps', function () {
    $user = User::factory()->create();
    $workspace = $user->currentWorkspace;
    $client = Client::factory()->create(['workspace_id' => $workspace->id]);

    // Create a default reminder sequence
    $sequence = InvoiceReminderSequence::create([
        'workspace_id' => $workspace->id,
        'name' => 'Default Reminders',
        'is_default' => true,
    ]);

    // Create one autosend step and one manual step
    InvoiceReminderStep::create([
        'invoice_reminder_sequence_id' => $sequence->id,
        'day_offset' => 3,
        'channel' => FollowUpChannel::Email,
        'reminder_type' => InvoiceReminderType::BeforeDue,
        'subject' => 'Payment Due Soon',
        'message_template' => 'Your payment is due soon.',
        'send_automatically' => true,
        'sort_order' => 1,
    ]);

    InvoiceReminderStep::create([
        'invoice_reminder_sequence_id' => $sequence->id,
        'day_offset' => 0,
        'channel' => FollowUpChannel::Email,
        'reminder_type' => InvoiceReminderType::OnDue,
        'subject' => 'Payment Due Today',
        'message_template' => 'Your payment is due today.',
        'send_automatically' => false, // Manual send only
        'sort_order' => 2,
    ]);

    // Create an invoice without sent_at initially
    $invoice = Invoice::create([
        'workspace_id' => $workspace->id,
        'client_id' => $client->id,
        'created_by' => $user->id,
        'invoice_number' => 'INV-001',
        'title' => 'Test Invoice',
        'currency' => 'USD',
        'subtotal' => 100.00,
        'tax_amount' => 10.00,
        'total' => 110.00,
        'due_date' => now()->addDays(7),
        'status' => 'draft',
        'sent_at' => null,
    ]);

    // Trigger observer by updating sent_at
    $invoice->update([
        'status' => 'sent',
        'sent_at' => now(),
    ]);

    // Check that only autosend reminders were created by observer
    $reminders = InvoiceReminder::where('invoice_id', $invoice->id)->get();

    expect($reminders)->toHaveCount(1)
        ->and($reminders->first()->days_offset)->toBe(3);
});

it('calculates scheduled date based on day offset', function () {
    $user = User::factory()->create();
    $workspace = $user->currentWorkspace;
    $client = Client::factory()->create(['workspace_id' => $workspace->id]);

    $sequence = InvoiceReminderSequence::create([
        'workspace_id' => $workspace->id,
        'name' => 'Default Reminders',
        'is_default' => true,
    ]);

    $step = InvoiceReminderStep::create([
        'invoice_reminder_sequence_id' => $sequence->id,
        'day_offset' => 3,
        'channel' => FollowUpChannel::Email,
        'reminder_type' => InvoiceReminderType::BeforeDue,
        'subject' => 'Payment Due Soon',
        'message_template' => 'Your payment is due soon.',
        'send_automatically' => true,
        'sort_order' => 1,
    ]);

    $dueDate = now()->addDays(10);
    $invoice = Invoice::create([
        'workspace_id' => $workspace->id,
        'client_id' => $client->id,
        'created_by' => $user->id,
        'invoice_number' => 'INV-001',
        'title' => 'Test Invoice',
        'currency' => 'USD',
        'subtotal' => 100.00,
        'tax_amount' => 10.00,
        'total' => 110.00,
        'due_date' => $dueDate,
        'status' => 'sent',
        'sent_at' => now(),
    ]);

    // Directly create reminder
    InvoiceReminder::create([
        'invoice_id' => $invoice->id,
        'workspace_id' => $workspace->id,
        'invoice_reminder_step_id' => $step->id,
        'reminder_type' => $step->reminder_type->value,
        'days_offset' => $step->day_offset,
        'channel' => $step->channel->value,
        'scheduled_at' => $dueDate->copy()->addDays($step->day_offset),
        'status' => 'pending',
    ]);

    $reminder = InvoiceReminder::where('invoice_id', $invoice->id)->first();

    expect($reminder->scheduled_at->format('Y-m-d'))->toBe($dueDate->copy()->addDays(3)->format('Y-m-d'));
});

it('cancels pending reminders when invoice is paid', function () {
    $user = User::factory()->create();
    $workspace = $user->currentWorkspace;
    $client = Client::factory()->create(['workspace_id' => $workspace->id]);

    $sequence = InvoiceReminderSequence::create([
        'workspace_id' => $workspace->id,
        'name' => 'Default Reminders',
        'is_default' => true,
    ]);

    InvoiceReminderStep::create([
        'invoice_reminder_sequence_id' => $sequence->id,
        'day_offset' => 3,
        'channel' => FollowUpChannel::Email,
        'reminder_type' => InvoiceReminderType::BeforeDue,
        'subject' => 'Payment Due Soon',
        'message_template' => 'Your payment is due soon.',
        'send_automatically' => true,
        'sort_order' => 1,
    ]);

    // Create an invoice and send it to schedule reminders
    $invoice = Invoice::create([
        'workspace_id' => $workspace->id,
        'client_id' => $client->id,
        'created_by' => $user->id,
        'invoice_number' => 'INV-001',
        'title' => 'Test Invoice',
        'currency' => 'USD',
        'subtotal' => 100.00,
        'tax_amount' => 10.00,
        'total' => 110.00,
        'due_date' => now()->addDays(7),
        'status' => 'draft',
        'sent_at' => null,
    ]);

    // Trigger observer by updating sent_at
    $invoice->update([
        'status' => 'sent',
        'sent_at' => now(),
    ]);

    // Verify reminders were created
    $reminders = InvoiceReminder::where('invoice_id', $invoice->id)->get();
    expect($reminders)->toHaveCount(1)
        ->and($reminders->first()->status)->toBe('pending');

    // Trigger observer by updating status to paid
    $invoice->update(['status' => 'paid']);

    // Verify reminders were cancelled by observer
    expect($reminders->fresh()->first()->status)->toBe('cancelled');
});

it('does not schedule reminders without default sequence', function () {
    $user = User::factory()->create();
    $workspace = $user->currentWorkspace;
    $client = Client::factory()->create(['workspace_id' => $workspace->id]);

    // No default sequence created

    // Create an invoice without sent_at initially
    $invoice = Invoice::create([
        'workspace_id' => $workspace->id,
        'client_id' => $client->id,
        'created_by' => $user->id,
        'invoice_number' => 'INV-001',
        'title' => 'Test Invoice',
        'currency' => 'USD',
        'subtotal' => 100.00,
        'tax_amount' => 10.00,
        'total' => 110.00,
        'due_date' => now()->addDays(7),
        'status' => 'draft',
        'sent_at' => null,
    ]);

    // Trigger observer by updating sent_at
    $invoice->update([
        'status' => 'sent',
        'sent_at' => now(),
    ]);

    // No reminders should be created by observer
    $reminders = InvoiceReminder::where('invoice_id', $invoice->id)->get();

    expect($reminders)->toHaveCount(0);
});

it('does not schedule reminders without due date', function () {
    $user = User::factory()->create();
    $workspace = $user->currentWorkspace;
    $client = Client::factory()->create(['workspace_id' => $workspace->id]);

    $sequence = InvoiceReminderSequence::create([
        'workspace_id' => $workspace->id,
        'name' => 'Default Reminders',
        'is_default' => true,
    ]);

    InvoiceReminderStep::create([
        'invoice_reminder_sequence_id' => $sequence->id,
        'day_offset' => 3,
        'channel' => FollowUpChannel::Email,
        'reminder_type' => InvoiceReminderType::BeforeDue,
        'subject' => 'Payment Due Soon',
        'message_template' => 'Your payment is due soon.',
        'send_automatically' => true,
        'sort_order' => 1,
    ]);

    // Invoice without due date
    $invoice = Invoice::create([
        'workspace_id' => $workspace->id,
        'client_id' => $client->id,
        'created_by' => $user->id,
        'invoice_number' => 'INV-001',
        'title' => 'Test Invoice',
        'currency' => 'USD',
        'subtotal' => 100.00,
        'tax_amount' => 10.00,
        'total' => 110.00,
        'due_date' => null,
        'status' => 'draft',
        'sent_at' => null,
    ]);

    // Trigger observer by updating sent_at
    $invoice->update([
        'status' => 'sent',
        'sent_at' => now(),
    ]);

    // No reminders should be created by observer
    $reminders = InvoiceReminder::where('invoice_id', $invoice->id)->get();

    expect($reminders)->toHaveCount(0);
});

it('can manually create a reminder for bypass autosend', function () {
    $user = User::factory()->create();
    $workspace = $user->currentWorkspace;
    $client = Client::factory()->create(['workspace_id' => $workspace->id]);

    $sequence = InvoiceReminderSequence::create([
        'workspace_id' => $workspace->id,
        'name' => 'Default Reminders',
        'is_default' => true,
    ]);

    $step = InvoiceReminderStep::create([
        'invoice_reminder_sequence_id' => $sequence->id,
        'day_offset' => 0,
        'channel' => FollowUpChannel::Email,
        'reminder_type' => InvoiceReminderType::OnDue,
        'subject' => 'Payment Due Today',
        'message_template' => 'Your payment is due today.',
        'send_automatically' => false,
        'sort_order' => 1,
    ]);

    $invoice = Invoice::create([
        'workspace_id' => $workspace->id,
        'client_id' => $client->id,
        'created_by' => $user->id,
        'invoice_number' => 'INV-001',
        'title' => 'Test Invoice',
        'currency' => 'USD',
        'subtotal' => 100.00,
        'tax_amount' => 10.00,
        'total' => 110.00,
        'due_date' => now()->addDays(7),
        'status' => 'sent',
        'sent_at' => now(),
    ]);

    // Manually create a reminder (bypass autosend)
    $manualReminder = InvoiceReminder::create([
        'invoice_id' => $invoice->id,
        'workspace_id' => $workspace->id,
        'invoice_reminder_step_id' => $step->id,
        'reminder_type' => $step->reminder_type->value,
        'days_offset' => 0,
        'channel' => $step->channel->value,
        'scheduled_at' => now(),
        'status' => 'pending',
    ]);

    expect($manualReminder)
        ->toBeInstanceOf(InvoiceReminder::class)
        ->and($manualReminder->status)->toBe('pending');
});

it('can send a reminder manually', function () {
    $user = User::factory()->create();
    $workspace = $user->currentWorkspace;
    $client = Client::factory()->create(['workspace_id' => $workspace->id]);

    $sequence = InvoiceReminderSequence::create([
        'workspace_id' => $workspace->id,
        'name' => 'Default Reminders',
        'is_default' => true,
    ]);

    $step = InvoiceReminderStep::create([
        'invoice_reminder_sequence_id' => $sequence->id,
        'day_offset' => 0,
        'channel' => FollowUpChannel::Email,
        'reminder_type' => InvoiceReminderType::OnDue,
        'subject' => 'Payment Due Today',
        'message_template' => 'Your payment is due today.',
        'send_automatically' => false,
        'sort_order' => 1,
    ]);

    $invoice = Invoice::create([
        'workspace_id' => $workspace->id,
        'client_id' => $client->id,
        'created_by' => $user->id,
        'invoice_number' => 'INV-001',
        'title' => 'Test Invoice',
        'currency' => 'USD',
        'subtotal' => 100.00,
        'tax_amount' => 10.00,
        'total' => 110.00,
        'due_date' => now()->addDays(7),
        'status' => 'sent',
        'sent_at' => now(),
    ]);

    $reminder = InvoiceReminder::create([
        'invoice_id' => $invoice->id,
        'workspace_id' => $workspace->id,
        'invoice_reminder_step_id' => $step->id,
        'reminder_type' => $step->reminder_type->value,
        'days_offset' => 0,
        'channel' => $step->channel->value,
        'scheduled_at' => now(),
        'status' => 'pending',
    ]);

    // Mark as sent
    $reminder->update([
        'status' => 'sent',
        'sent_at' => now(),
    ]);

    expect($reminder->fresh()->status)->toBe('sent')
        ->and($reminder->fresh()->sent_at)->not->toBeNull();
});

it('can handle different reminder types', function () {
    $user = User::factory()->create();
    $workspace = $user->currentWorkspace;

    $sequence = InvoiceReminderSequence::create([
        'workspace_id' => $workspace->id,
        'name' => 'Default Reminders',
        'is_default' => true,
    ]);

    $beforeDueStep = InvoiceReminderStep::create([
        'invoice_reminder_sequence_id' => $sequence->id,
        'day_offset' => 3,
        'channel' => FollowUpChannel::Email,
        'reminder_type' => InvoiceReminderType::BeforeDue,
        'subject' => 'Payment Due Soon',
        'message_template' => 'Your payment is due soon.',
        'send_automatically' => true,
        'sort_order' => 1,
    ]);

    $onDueStep = InvoiceReminderStep::create([
        'invoice_reminder_sequence_id' => $sequence->id,
        'day_offset' => 0,
        'channel' => FollowUpChannel::Email,
        'reminder_type' => InvoiceReminderType::OnDue,
        'subject' => 'Payment Due Today',
        'message_template' => 'Your payment is due today.',
        'send_automatically' => true,
        'sort_order' => 2,
    ]);

    $afterDueStep = InvoiceReminderStep::create([
        'invoice_reminder_sequence_id' => $sequence->id,
        'day_offset' => -3,
        'channel' => FollowUpChannel::Email,
        'reminder_type' => InvoiceReminderType::AfterDue,
        'subject' => 'Payment Overdue',
        'message_template' => 'Your payment is overdue.',
        'send_automatically' => true,
        'sort_order' => 3,
    ]);

    expect($beforeDueStep->reminder_type)->toBe(InvoiceReminderType::BeforeDue)
        ->and($onDueStep->reminder_type)->toBe(InvoiceReminderType::OnDue)
        ->and($afterDueStep->reminder_type)->toBe(InvoiceReminderType::AfterDue);
});

it('can handle different channels', function () {
    $user = User::factory()->create();
    $workspace = $user->currentWorkspace;

    $sequence = InvoiceReminderSequence::create([
        'workspace_id' => $workspace->id,
        'name' => 'Default Reminders',
        'is_default' => true,
    ]);

    $emailStep = InvoiceReminderStep::create([
        'invoice_reminder_sequence_id' => $sequence->id,
        'day_offset' => 3,
        'channel' => FollowUpChannel::Email,
        'reminder_type' => InvoiceReminderType::BeforeDue,
        'subject' => 'Payment Due Soon',
        'message_template' => 'Your payment is due soon.',
        'send_automatically' => true,
        'sort_order' => 1,
    ]);

    $smsStep = InvoiceReminderStep::create([
        'invoice_reminder_sequence_id' => $sequence->id,
        'day_offset' => 0,
        'channel' => FollowUpChannel::Sms,
        'reminder_type' => InvoiceReminderType::OnDue,
        'subject' => 'Payment Due',
        'message_template' => 'Your payment is due.',
        'send_automatically' => true,
        'sort_order' => 2,
    ]);

    expect($emailStep->channel)->toBe(FollowUpChannel::Email)
        ->and($smsStep->channel)->toBe(FollowUpChannel::Sms);
});

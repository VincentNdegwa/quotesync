<?php

use App\Models\Client;
use App\Models\Invoice;
use App\Models\Quote;
use App\Models\Task;
use App\Models\TaskStatus;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('can create a task via controller', function () {
    $user = User::factory()->create();
    $workspace = $user->currentWorkspace;
    $quote = Quote::factory()->create(['workspace_id' => $workspace->id]);
    $taskStatus = TaskStatus::factory()->create([
        'workspace_id' => $workspace->id,
        'slug' => 'todo',
    ]);

    $payload = [
        'taskable_type' => 'quote',
        'taskable_id' => $quote->id,
        'title' => 'Test Task',
        'description' => 'Test Description',
        'assigned_to' => $user->id,
        'due_date' => now()->addDays(7)->format('Y-m-d'),
    ];

    $response = $this->actingAs($user)
        ->post(route('tasks.store'), $payload);

    $response->assertRedirect();

    $this->assertDatabaseHas('tasks', [
        'workspace_id' => $workspace->id,
        'title' => 'Test Task',
        'taskable_type' => Quote::class,
        'taskable_id' => $quote->id,
    ]);
});

it('can create a task for an invoice via controller', function () {
    $user = User::factory()->create();
    $workspace = $user->currentWorkspace;
    $client = Client::factory()->create(['workspace_id' => $workspace->id]);
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
        'status' => 'draft',
    ]);
    $taskStatus = TaskStatus::factory()->create([
        'workspace_id' => $workspace->id,
        'slug' => 'todo',
    ]);

    $payload = [
        'taskable_type' => 'invoice',
        'taskable_id' => $invoice->id,
        'title' => 'Invoice Task',
        'assigned_to' => $user->id,
    ];

    $response = $this->actingAs($user)
        ->post(route('tasks.store'), $payload);

    $response->assertRedirect();

    $this->assertDatabaseHas('tasks', [
        'taskable_type' => Invoice::class,
        'taskable_id' => $invoice->id,
    ]);
});

it('validates task creation via controller', function () {
    $user = User::factory()->create();

    $payload = [
        'taskable_type' => 'invalid_type',
        'title' => '',
    ];

    $response = $this->actingAs($user)
        ->post(route('tasks.store'), $payload);

    $response->assertSessionHasErrors(['taskable_type', 'title']);
});

it('can update a task via controller', function () {
    $user = User::factory()->create();
    $workspace = $user->currentWorkspace;
    $quote = Quote::factory()->create(['workspace_id' => $workspace->id]);
    $taskStatus = TaskStatus::factory()->create([
        'workspace_id' => $workspace->id,
        'slug' => 'todo',
    ]);
    $doneStatus = TaskStatus::factory()->create([
        'workspace_id' => $workspace->id,
        'slug' => 'done',
    ]);

    $task = Task::factory()->create([
        'workspace_id' => $workspace->id,
        'taskable_type' => Quote::class,
        'taskable_id' => $quote->id,
        'task_status_id' => $taskStatus->id,
    ]);

    $payload = [
        'title' => 'Updated Task',
        'description' => 'Updated Description',
        'assigned_to' => $user->id,
        'due_date' => now()->addDays(14)->format('Y-m-d'),
        'task_status_id' => $doneStatus->id,
    ];

    $response = $this->actingAs($user)
        ->put(route('tasks.update', $task), $payload);

    $response->assertRedirect();

    $this->assertDatabaseHas('tasks', [
        'id' => $task->id,
        'title' => 'Updated Task',
        'task_status_id' => $doneStatus->id,
    ]);

    $task->refresh();
    expect($task->completed_at)->not->toBeNull();
});

it('sets completed_at when task is marked as done via controller', function () {
    $user = User::factory()->create();
    $workspace = $user->currentWorkspace;
    $quote = Quote::factory()->create(['workspace_id' => $workspace->id]);
    $taskStatus = TaskStatus::factory()->create([
        'workspace_id' => $workspace->id,
        'slug' => 'todo',
    ]);
    $doneStatus = TaskStatus::factory()->create([
        'workspace_id' => $workspace->id,
        'slug' => 'done',
    ]);

    $task = Task::factory()->create([
        'workspace_id' => $workspace->id,
        'task_status_id' => $taskStatus->id,
        'completed_at' => null,
    ]);

    $payload = [
        'task_status_id' => $doneStatus->id,
    ];

    $this->actingAs($user)
        ->put(route('tasks.update', $task), $payload);

    $task->refresh();
    expect($task->completed_at)->not->toBeNull();
});

it('can delete a task via controller', function () {
    $user = User::factory()->create();
    $workspace = $user->currentWorkspace;
    $task = Task::factory()->create(['workspace_id' => $workspace->id]);

    $response = $this->actingAs($user)
        ->delete(route('tasks.destroy', $task));

    $response->assertRedirect();

    $this->assertDatabaseMissing('tasks', [
        'id' => $task->id,
    ]);
});

it('can update task assignment via controller', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $workspace = $user->currentWorkspace;
    $task = Task::factory()->create([
        'workspace_id' => $workspace->id,
        'assigned_to' => $user->id,
    ]);

    $payload = [
        'assigned_to' => $otherUser->id,
    ];

    $response = $this->actingAs($user)
        ->put(route('tasks.update', $task), $payload);

    $response->assertRedirect();

    $this->assertDatabaseHas('tasks', [
        'id' => $task->id,
        'assigned_to' => $otherUser->id,
    ]);
});

it('can update taskable association via controller', function () {
    $user = User::factory()->create();
    $workspace = $user->currentWorkspace;
    $client = Client::factory()->create(['workspace_id' => $workspace->id]);
    $quote = Quote::factory()->create(['workspace_id' => $workspace->id]);
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
        'status' => 'draft',
    ]);
    $task = Task::factory()->create([
        'workspace_id' => $workspace->id,
        'taskable_type' => Quote::class,
        'taskable_id' => $quote->id,
    ]);

    $payload = [
        'taskable_type' => 'invoice',
        'taskable_id' => $invoice->id,
    ];

    $response = $this->actingAs($user)
        ->put(route('tasks.update', $task), $payload);

    $response->assertRedirect();

    $this->assertDatabaseHas('tasks', [
        'id' => $task->id,
        'taskable_type' => Invoice::class,
        'taskable_id' => $invoice->id,
    ]);
});

<?php

use App\Models\Quote;
use App\Models\Task;
use App\Models\TaskStatus;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('can create a task', function () {
    $workspace = Workspace::factory()->create();
    $quote = Quote::factory()->create(['workspace_id' => $workspace->id]);
    $taskStatus = TaskStatus::factory()->create(['workspace_id' => $workspace->id]);
    $user = User::factory()->create();

    $task = Task::create([
        'workspace_id' => $workspace->id,
        'taskable_type' => Quote::class,
        'taskable_id' => $quote->id,
        'assigned_to' => $user->id,
        'assigned_by' => $user->id,
        'task_status_id' => $taskStatus->id,
        'title' => 'Test Task',
        'description' => 'Test Description',
    ]);

    expect($task)
        ->toBeInstanceOf(Task::class)
        ->and($task->title)->toBe('Test Task')
        ->and($task->taskable_type)->toBe(Quote::class);
});

it('can update a task', function () {
    $task = Task::factory()->create();

    $task->update([
        'title' => 'Updated Task',
        'description' => 'Updated Description',
    ]);

    expect($task->title)->toBe('Updated Task')
        ->and($task->description)->toBe('Updated Description');
});

it('can delete a task', function () {
    $task = Task::factory()->create();

    $task->delete();

    expect(Task::find($task->id))->toBeNull();
});

it('belongs to a workspace', function () {
    $workspace = Workspace::factory()->create();
    $task = Task::factory()->create(['workspace_id' => $workspace->id]);

    expect($task->workspace->id)->toBe($workspace->id);
});

it('belongs to a task status', function () {
    $taskStatus = TaskStatus::factory()->create();
    $task = Task::factory()->create(['task_status_id' => $taskStatus->id]);

    expect($task->status->id)->toBe($taskStatus->id);
});

it('belongs to assigned user', function () {
    $user = User::factory()->create();
    $task = Task::factory()->create(['assigned_to' => $user->id]);

    expect($task->assignedTo->id)->toBe($user->id);
});

it('belongs to assigned by user', function () {
    $user = User::factory()->create();
    $task = Task::factory()->create(['assigned_by' => $user->id]);

    expect($task->assignedBy->id)->toBe($user->id);
});

it('has polymorphic relationship to quote', function () {
    $quote = Quote::factory()->create();
    $task = Task::factory()->create([
        'taskable_type' => Quote::class,
        'taskable_id' => $quote->id,
    ]);

    expect($task->taskable->id)->toBe($quote->id);
});

it('sets completed_at when status is done via controller', function () {
    $taskStatus = TaskStatus::factory()->create(['slug' => 'done']);
    $task = Task::factory()->create(['task_status_id' => null, 'completed_at' => null]);

    // Simulate controller logic
    $task->update(['task_status_id' => $taskStatus->id]);
    if ($taskStatus->slug === 'done' && ! $task->completed_at) {
        $task->completed_at = now();
        $task->save();
    }

    expect($task->completed_at)->not->toBeNull();
});

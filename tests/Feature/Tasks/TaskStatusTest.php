<?php

use App\Models\TaskStatus;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('can create a task status', function () {
    $workspace = Workspace::factory()->create();
    $taskStatus = TaskStatus::factory()->create([
        'workspace_id' => $workspace->id,
    ]);

    expect($taskStatus)
        ->toBeInstanceOf(TaskStatus::class)
        ->and($taskStatus->workspace_id)->toBe($workspace->id);
});

it('can update a task status', function () {
    $taskStatus = TaskStatus::factory()->create();

    $taskStatus->update([
        'name' => 'Updated Status',
        'color' => '#ff0000',
    ]);

    expect($taskStatus->name)->toBe('Updated Status')
        ->and($taskStatus->color)->toBe('#ff0000');
});

it('can delete a task status', function () {
    $taskStatus = TaskStatus::factory()->create();

    $taskStatus->delete();

    expect(TaskStatus::find($taskStatus->id))->toBeNull();
});

it('belongs to a workspace', function () {
    $workspace = Workspace::factory()->create();
    $taskStatus = TaskStatus::factory()->create([
        'workspace_id' => $workspace->id,
    ]);

    expect($taskStatus->workspace->id)->toBe($workspace->id);
});

it('has many tasks', function () {
    $taskStatus = TaskStatus::factory()->create();
    $tasks = \App\Models\Task::factory()->count(3)->create([
        'task_status_id' => $taskStatus->id,
    ]);

    expect($taskStatus->tasks)->toHaveCount(3);
});

it('can create default statuses for a workspace', function () {
    $workspace = Workspace::factory()->create();

    $this->artisan('db:seed', ['--class' => 'TaskStatusSeeder']);

    $taskStatuses = TaskStatus::where('workspace_id', $workspace->id)->get();

    expect($taskStatuses->count())->toBeGreaterThanOrEqual(2); // At least todo and done
});

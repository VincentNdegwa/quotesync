<?php

use App\Models\TaskStatus;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('can create a task status via controller', function () {
    $user = User::factory()->create();
    $workspace = $user->currentWorkspace;
    $user->addRole('admin', $workspace);

    $payload = [
        'name' => 'In Progress',
        'color' => '#FFA500',
    ];

    $response = $this->actingAs($user)
        ->post(route('configuration.task-status.store'), $payload);

    $response->assertRedirect();

    $this->assertDatabaseHas('task_statuses', [
        'workspace_id' => $workspace->id,
        'name' => 'In Progress',
        'slug' => 'in-progress',
    ]);
});

it('can update a task status via controller', function () {
    $user = User::factory()->create();
    $workspace = $user->currentWorkspace;
    $user->addRole('admin', $workspace);
    $taskStatus = TaskStatus::factory()->create([
        'workspace_id' => $workspace->id,
        'is_system' => false,
    ]);

    $payload = [
        'name' => 'In Review',
        'color' => '#0000FF',
    ];

    $response = $this->actingAs($user)
        ->put(route('configuration.task-status.update', $taskStatus), $payload);

    $response->assertRedirect();

    $taskStatus->refresh();
    expect($taskStatus->name)->toBe('In Review');
});

it('cannot update a system task status via controller', function () {
    $user = User::factory()->create();
    $workspace = $user->currentWorkspace;
    $user->addRole('admin', $workspace);
    $taskStatus = TaskStatus::factory()->create([
        'workspace_id' => $workspace->id,
        'is_system' => true,
    ]);

    $payload = [
        'name' => 'Modified System',
        'color' => '#0000FF',
    ];

    $response = $this->actingAs($user)
        ->put(route('configuration.task-status.update', $taskStatus), $payload);

    $response->assertRedirect();

    $taskStatus->refresh();
    expect($taskStatus->name)->not->toBe('Modified System');
});

it('can delete a task status via controller', function () {
    $user = User::factory()->create();
    $workspace = $user->currentWorkspace;
    $user->addRole('admin', $workspace);
    $taskStatus = TaskStatus::factory()->create([
        'workspace_id' => $workspace->id,
        'is_system' => false,
    ]);

    $taskStatusId = $taskStatus->id;

    $response = $this->actingAs($user)
        ->delete(route('configuration.task-status.destroy', $taskStatus));

    $response->assertRedirect();

    $this->assertDatabaseMissing('task_statuses', ['id' => $taskStatusId]);
});

it('cannot delete a system task status via controller', function () {
    $user = User::factory()->create();
    $workspace = $user->currentWorkspace;
    $user->addRole('admin', $workspace);
    $taskStatus = TaskStatus::factory()->create([
        'workspace_id' => $workspace->id,
        'is_system' => true,
    ]);

    $response = $this->actingAs($user)
        ->delete(route('configuration.task-status.destroy', $taskStatus));

    $response->assertRedirect();

    $taskStatus->refresh();
    expect($taskStatus->deleted_at)->toBeNull();
});

it('can reorder task statuses via controller', function () {
    $user = User::factory()->create();
    $workspace = $user->currentWorkspace;
    $user->addRole('admin', $workspace);
    $status1 = TaskStatus::factory()->create([
        'workspace_id' => $workspace->id,
        'sort_order' => 1,
    ]);
    $status2 = TaskStatus::factory()->create([
        'workspace_id' => $workspace->id,
        'sort_order' => 2,
    ]);

    $payload = [
        'taskStatuses' => [
            ['id' => $status1->id, 'sort_order' => 2],
            ['id' => $status2->id, 'sort_order' => 1],
        ],
    ];

    $response = $this->actingAs($user)
        ->put(route('configuration.task-status.reorder'), $payload);

    $response->assertRedirect();

    $status1->refresh();
    $status2->refresh();
    expect($status1->sort_order)->toBe(2);
    expect($status2->sort_order)->toBe(1);
});

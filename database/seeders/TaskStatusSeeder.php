<?php

namespace Database\Seeders;

use App\Models\TaskStatus;
use App\Models\Workspace;
use Illuminate\Database\Seeder;

class TaskStatusSeeder extends Seeder
{
    public function run(): void
    {
        Workspace::chunk(100, function ($workspaces) {
            foreach ($workspaces as $workspace) {
                $this->seedForWorkspace($workspace);
            }
        });
    }

    public function seedForWorkspace(Workspace $workspace): void
    {
        // System statuses: todo (first) and done (last) - cannot be edited or deleted
        $systemStatuses = [
            [
                'name' => 'To Do',
                'slug' => 'todo',
                'color' => '#64748b',
                'sort_order' => 1,
                'is_default' => true,
                'is_system' => true,
            ],
            [
                'name' => 'Done',
                'slug' => 'done',
                'color' => '#10b981',
                'sort_order' => 4,
                'is_default' => true,
                'is_system' => true,
            ],
        ];

        // Custom statuses - can be edited or deleted
        $customStatuses = [
            [
                'name' => 'In Progress',
                'slug' => 'in_progress',
                'color' => '#3b82f6',
                'sort_order' => 2,
                'is_default' => true,
                'is_system' => false,
            ],
            [
                'name' => 'In Review',
                'slug' => 'in_review',
                'color' => '#f59e0b',
                'sort_order' => 3,
                'is_default' => true,
                'is_system' => false,
            ],
        ];

        foreach ($systemStatuses as $status) {
            TaskStatus::query()->firstOrCreate(
                [
                    'workspace_id' => $workspace->id,
                    'slug' => $status['slug'],
                ],
                [
                    'name' => $status['name'],
                    'color' => $status['color'],
                    'sort_order' => $status['sort_order'],
                    'is_default' => $status['is_default'],
                    'is_system' => $status['is_system'],
                ]
            );
        }

        foreach ($customStatuses as $status) {
            TaskStatus::query()->firstOrCreate(
                [
                    'workspace_id' => $workspace->id,
                    'slug' => $status['slug'],
                ],
                [
                    'name' => $status['name'],
                    'color' => $status['color'],
                    'sort_order' => $status['sort_order'],
                    'is_default' => $status['is_default'],
                    'is_system' => $status['is_system'],
                ]
            );
        }
    }
}

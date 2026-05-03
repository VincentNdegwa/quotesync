<?php

namespace App\Listeners;

use App\Events\WorkspaceCreated;
use Database\Seeders\DefaultIndustriesSeeder;
use Database\Seeders\DefaultUnitsSeeder;
use Database\Seeders\TaskStatusSeeder;

class SeedWorkspaceDefaults
{
    public function handle(WorkspaceCreated $event): void
    {
        (new DefaultUnitsSeeder())->seedForWorkspace($event->workspace);
        (new DefaultIndustriesSeeder())->seedForWorkspace($event->workspace);
        (new TaskStatusSeeder())->seedForWorkspace($event->workspace);
    }
}

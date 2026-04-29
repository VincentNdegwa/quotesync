<?php

namespace App\Listeners;

use App\Events\WorkspaceCreated;
use Database\Seeders\ClientIndustrySeeder;
use Database\Seeders\DefaultUnitsSeeder;

class SeedWorkspaceDefaults
{
    public function handle(WorkspaceCreated $event): void
    {
        (new DefaultUnitsSeeder())->seedForWorkspace($event->workspace);
        (new ClientIndustrySeeder())->seedForWorkspace($event->workspace);
    }
}

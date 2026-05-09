<?php

namespace App\Events;

use App\Models\Workspace;
use Illuminate\Foundation\Events\Dispatchable;

class WorkspaceCreated
{
    use Dispatchable;

    public function __construct(
        public Workspace $workspace
    ) {}
}

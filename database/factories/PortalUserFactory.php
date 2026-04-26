<?php

namespace Database\Factories;

use App\Models\Client;
use App\Models\PortalUser;
use App\Models\Workspace;
use Illuminate\Database\Eloquent\Factories\Factory;

class PortalUserFactory extends Factory
{
    protected $model = PortalUser::class;

    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'workspace_id' => Workspace::factory(),
            'client_id' => Client::factory(),
        ];
    }
}

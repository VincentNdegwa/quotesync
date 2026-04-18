<?php

namespace Database\Factories;

use App\Models\ConfigurationTag;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ConfigurationTag>
 */
class ConfigurationTagFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $creator = User::factory();

        return [
            'created_by' => $creator,
            'workspace_id' => fn (array $attributes): int => User::query()->findOrFail($attributes['created_by'])->current_workspace_id,
            'name' => fake()->unique()->word(),
            'is_active' => true,
        ];
    }
}

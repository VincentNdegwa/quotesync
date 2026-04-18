<?php

namespace Database\Factories;

use App\Models\ConfigurationUnit;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ConfigurationUnit>
 */
class ConfigurationUnitFactory extends Factory
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
            'name' => fake()->unique()->randomElement(['Hour', 'Day', 'Package', 'Kilogram', 'Meter']),
            'symbol' => fake()->randomElement(['hr', 'day', 'pkg', 'kg', 'm']),
            'is_active' => true,
        ];
    }
}

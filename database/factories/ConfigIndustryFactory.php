<?php

namespace Database\Factories;

use App\Models\ConfigIndustry;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ConfigIndustry>
 */
class ConfigIndustryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'workspace_id' => function (array $attributes) {
                return $attributes['workspace_id'] ?? Workspace::factory();
            },
            'created_by' => User::factory(),
            'name' => fake()->word(),
            'description' => fake()->sentence(),
            'icon' => fake()->randomElement(['briefcase', 'factory', 'building', 'truck', 'palette', 'scale', 'code', 'stethoscope', 'bank', 'shopping-bag', 'utensils', 'graduation-cap']),
            'color' => fake()->hexColor(),
            'is_active' => true,
        ];
    }
}

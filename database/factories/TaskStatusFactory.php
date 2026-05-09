<?php

namespace Database\Factories;

use App\Models\TaskStatus;
use App\Models\Workspace;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TaskStatus>
 */
class TaskStatusFactory extends Factory
{
    protected $model = TaskStatus::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'workspace_id' => Workspace::factory(),
            'name' => fake()->words(2, true),
            'slug' => fake()->slug(),
            'color' => fake()->hexColor(),
            'sort_order' => fake()->numberBetween(1, 100),
            'is_default' => false,
        ];
    }

    public function defaultStatuses(): self
    {
        return $this->state(fn (array $attributes) => [
            'name' => fake()->randomElement(['To Do', 'In Progress', 'In Review', 'Done']),
            'slug' => fake()->randomElement(['todo', 'in_progress', 'in_review', 'done']),
            'color' => fake()->randomElement(['#64748b', '#3b82f6', '#f59e0b', '#10b981']),
            'sort_order' => fake()->numberBetween(1, 4),
            'is_default' => true,
        ]);
    }
}

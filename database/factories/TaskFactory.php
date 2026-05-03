<?php

namespace Database\Factories;

use App\Models\Task;
use App\Models\TaskStatus;
use App\Models\User;
use App\Models\Workspace;
use App\Models\Quote;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Task>
 */
class TaskFactory extends Factory
{
    protected $model = Task::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'workspace_id' => Workspace::factory(),
            'taskable_type' => Quote::class,
            'taskable_id' => Quote::factory(),
            'assigned_to' => User::factory(),
            'assigned_by' => User::factory(),
            'task_status_id' => TaskStatus::factory(),
            'title' => fake()->sentence(),
            'description' => fake()->paragraph(),
            'due_date' => fake()->date(),
            'completed_at' => fake()->optional()->dateTime(),
        ];
    }

    public function forQuote(Quote $quote): self
    {
        return $this->state(fn (array $attributes) => [
            'workspace_id' => $quote->workspace_id,
            'taskable_type' => Quote::class,
            'taskable_id' => $quote->id,
        ]);
    }
}

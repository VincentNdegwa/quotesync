<?php

namespace Database\Factories;

use App\Models\ApprovalRule;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<ApprovalRule>
 */
class ApprovalRuleFactory extends Factory
{
    protected $model = ApprovalRule::class;

    public function definition(): array
    {
        return [
            'workspace_id' => Workspace::factory(),
            'client_id' => null,
            'approver_id' => User::factory(),
            'trigger_type' => fake()->randomElement(['value_above', 'value_below', 'client', 'all_quotes']),
            'threshold_value' => fake()->randomFloat(2, 1000, 50000),
            'is_active' => true,
        ];
    }
}

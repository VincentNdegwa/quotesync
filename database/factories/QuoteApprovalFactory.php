<?php

namespace Database\Factories;

use App\Enums\QuoteApprovalStatus;
use App\Models\Quote;
use App\Models\QuoteApproval;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<QuoteApproval>
 */
class QuoteApprovalFactory extends Factory
{
    protected $model = QuoteApproval::class;

    public function definition(): array
    {
        return [
            'quote_id' => Quote::factory(),
            'approval_rule_id' => null,
            'approver_id' => User::factory(),
            'status' => QuoteApprovalStatus::Pending->value,
        ];
    }
}

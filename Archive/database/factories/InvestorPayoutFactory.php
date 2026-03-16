<?php

namespace Database\Factories;

use App\Models\Investor;
use App\Models\InvestorPayout;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<InvestorPayout> */
class InvestorPayoutFactory extends Factory
{
    protected $model = InvestorPayout::class;

    public function definition(): array
    {
        return [
            'investor_id' => Investor::factory(),
            'paid_at' => fake()->dateTimeBetween('-3 months', 'now'),
            'amount' => fake()->numberBetween(500_000, 3_000_000),
        ];
    }
}

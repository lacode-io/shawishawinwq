<?php

namespace Database\Factories;

use App\Enums\InvestorStatus;
use App\Models\Investor;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Investor> */
class InvestorFactory extends Factory
{
    protected $model = Investor::class;

    public function definition(): array
    {
        $amount = fake()->numberBetween(5_000_000, 50_000_000);
        $months = fake()->numberBetween(6, 24);
        $profitPercent = fake()->randomFloat(2, 5, 30);
        $totalDue = $amount + (int) round($amount * ($profitPercent / 100));

        return [
            'full_name' => fake()->name(),
            'phone' => '077' . fake()->numerify('########'),
            'amount_invested' => $amount,
            'investment_months' => $months,
            'profit_percent_total' => $profitPercent,
            'start_date' => fake()->dateTimeBetween('-6 months', 'now'),
            'payout_due_date' => fake()->dateTimeBetween('now', '+12 months'),
            'monthly_target_amount' => (int) ceil($totalDue / $months),
            'status' => InvestorStatus::Active,
        ];
    }

    public function completed(): static
    {
        return $this->state(fn (): array => [
            'status' => InvestorStatus::Completed,
        ]);
    }
}

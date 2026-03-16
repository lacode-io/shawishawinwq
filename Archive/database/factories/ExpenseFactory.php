<?php

namespace Database\Factories;

use App\Enums\ExpenseType;
use App\Models\Expense;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Expense> */
class ExpenseFactory extends Factory
{
    protected $model = Expense::class;

    public function definition(): array
    {
        return [
            'type' => fake()->randomElement(ExpenseType::cases()),
            'amount' => fake()->numberBetween(10_000, 500_000),
            'spent_at' => fake()->dateTimeBetween('-3 months', 'now'),
        ];
    }
}

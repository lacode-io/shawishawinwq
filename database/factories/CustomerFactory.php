<?php

namespace Database\Factories;

use App\Enums\CustomerStatus;
use App\Models\Customer;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Customer> */
class CustomerFactory extends Factory
{
    protected $model = Customer::class;

    public function definition(): array
    {
        $saleTotal = fake()->numberBetween(500_000, 2_000_000);
        $months = fake()->numberBetween(3, 12);

        return [
            'full_name' => fake()->name(),
            'phone' => '077' . fake()->numerify('########'),
            'address' => fake()->address(),
            'product_type' => fake()->randomElement(['هاتف', 'لابتوب', 'تلفزيون', 'ثلاجة']),
            'product_cost_price' => (int) round($saleTotal * 0.7),
            'product_sale_total' => $saleTotal,
            'delivery_date' => fake()->dateTimeBetween('-6 months', 'now'),
            'duration_months' => $months,
            'monthly_installment_amount' => (int) ceil($saleTotal / $months),
            'status' => CustomerStatus::Active,
        ];
    }

    public function completed(): static
    {
        return $this->state(fn (): array => [
            'status' => CustomerStatus::Completed,
        ]);
    }
}

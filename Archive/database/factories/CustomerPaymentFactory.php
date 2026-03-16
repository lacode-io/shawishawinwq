<?php

namespace Database\Factories;

use App\Enums\PaymentMethod;
use App\Models\Customer;
use App\Models\CustomerPayment;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<CustomerPayment> */
class CustomerPaymentFactory extends Factory
{
    protected $model = CustomerPayment::class;

    public function definition(): array
    {
        return [
            'customer_id' => Customer::factory(),
            'paid_at' => fake()->dateTimeBetween('-3 months', 'now'),
            'amount' => fake()->numberBetween(50_000, 200_000),
            'payment_method' => PaymentMethod::Cash,
        ];
    }
}

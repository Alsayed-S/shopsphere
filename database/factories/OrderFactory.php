<?php

namespace Database\Factories;

use App\Models\User;
use App\Modules\Orders\Enums\OrderStatus;
use App\Modules\Orders\Enums\PaymentStatus;
use App\Modules\Orders\Models\Order;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Order>
 */
class OrderFactory extends Factory
{
    protected $model = Order::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'order_number' => 'ORD-'.strtoupper(Str::random(12)),
            'status' => OrderStatus::PENDING,
            'payment_status' => PaymentStatus::PENDING,
            'shipping_name' => fake()->name(),
            'shipping_phone' => fake()->numerify('01#########'),
            'shipping_address' => fake()->address(),
            'subtotal' => '100.00',
            'total' => '100.00',
        ];
    }
}

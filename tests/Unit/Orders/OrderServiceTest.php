<?php

namespace Tests\Unit\Orders;

use App\Models\User;
use App\Modules\Catalog\Models\Product;
use App\Modules\Orders\Enums\OrderStatus;
use App\Modules\Orders\Enums\PaymentStatus;
use App\Modules\Orders\Services\OrderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_an_order_from_cart(): void
    {
        // Arrange
        $user = User::factory()->create();

        $product = Product::factory()
            ->published()
            ->create([
                'price' => '19.99',
            ]);

        $cart = $user->cart()->create();

        $cart->items()->create([
            'product_id' => $product->id,
            'quantity' => 2,
        ]);

        $shippingData = [
            'shipping_name' => 'Test Customer',
            'shipping_phone' => '01234567890',
            'shipping_address' => 'Cairo, Egypt',
        ];

        $orderService = app(OrderService::class);

        // Act
        $order = $orderService->createFromCart(
            $user,
            $shippingData
        );

        // Assert
        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'user_id' => $user->id,
            'status' => OrderStatus::PENDING->value,
            'payment_status' => PaymentStatus::PENDING->value,
            'subtotal' => '39.98',
            'total' => '39.98',
        ]);

        $this->assertDatabaseHas('order_items', [
            'order_id' => $order->id,
            'product_id' => $product->id,
            'product_name' => $product->name,
            'unit_price' => '19.99',
            'quantity' => 2,
            'subtotal' => '39.98',
        ]);

        $this->assertDatabaseCount('cart_items', 0);

        $this->assertCount(1, $order->items);
    }


    public function test_it_cannot_create_an_order_from_an_empty_cart(): void
    {
        // Arrange
        $user = User::factory()->create();

        $shippingData = [
            'shipping_name' => 'Test Customer',
            'shipping_phone' => '01234567890',
            'shipping_address' => 'Cairo, Egypt',
        ];

        $orderService = app(OrderService::class);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'Cannot create an order from an empty cart.'
        );

        // Act
        $orderService->createFromCart($user, $shippingData);
    }


    public function test_it_cannot_create_an_order_with_unavailable_products(): void
    {
        // Arrange
        $user = User::factory()->create();

        $product = Product::factory()->create([
            'price' => '19.99',
            'status' => 'draft',
        ]);

        $cart = $user->cart()->create();

        $cart->items()->create([
            'product_id' => $product->id,
            'quantity' => 2,
        ]);

        $shippingData = [
            'shipping_name' => 'Test Customer',
            'shipping_phone' => '01234567890',
            'shipping_address' => 'Cairo, Egypt',
        ];

        $orderService = app(OrderService::class);

        // Assert
        try {
            $orderService->createFromCart($user, $shippingData);
            $this->fail('Expected an exception for unavailable products.');
        } catch (\InvalidArgumentException $exception) {
            $this->assertSame(
                'One or more products are unavailable.',
                $exception->getMessage()
            );
        }

        $this->assertDatabaseCount('orders', 0);
        $this->assertDatabaseCount('cart_items', 1);
    }
}

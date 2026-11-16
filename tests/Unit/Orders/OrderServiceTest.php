<?php

namespace Tests\Unit\Orders;

use App\Models\User;
use App\Modules\Catalog\Models\Product;
use App\Modules\Orders\Enums\OrderStatus;
use App\Modules\Orders\Enums\PaymentStatus;
use App\Modules\Orders\Models\Order;
use App\Modules\Orders\Services\OrderService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
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
        $order = $orderService->createFromCart($user, $shippingData);

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

    public function test_user_can_cancel_a_pending_order(): void
    {
        $this->assertOrderCanBeCancelled(OrderStatus::PENDING);
    }

    public function test_user_can_cancel_a_confirmed_order(): void
    {
        $this->assertOrderCanBeCancelled(OrderStatus::CONFIRMED);
    }

    public function test_user_can_cancel_a_processing_order(): void
    {
        $this->assertOrderCanBeCancelled(OrderStatus::PROCESSING);
    }

    public function test_user_cannot_cancel_a_shipped_order(): void
    {
        $this->assertOrderCannotBeCancelled(OrderStatus::SHIPPED);
    }

    public function test_user_cannot_cancel_a_delivered_order(): void
    {
        $this->assertOrderCannotBeCancelled(OrderStatus::DELIVERED);
    }

    public function test_user_cannot_cancel_an_already_cancelled_order(): void
    {
        $this->assertOrderCannotBeCancelled(OrderStatus::CANCELLED);
    }

    public function test_user_cannot_cancel_an_order_belonging_to_another_user(): void
    {
        // Arrange
        $owner = User::factory()->create();
        $anotherUser = User::factory()->create();

        $order = $this->createOrder($owner, OrderStatus::PENDING);

        $orderService = app(OrderService::class);

        // Assert
        try {
            $orderService->cancelOrder($anotherUser, $order->id);
            $this->fail('Expected an exception for an order belonging to another user.');
        } catch (ModelNotFoundException) {
            $this->assertDatabaseHas('orders', [
                'id' => $order->id,
                'status' => OrderStatus::PENDING->value,
            ]);
        }
    }

    public function test_cancelling_an_order_does_not_change_its_payment_status(): void
    {
        // Arrange
        $user = User::factory()->create();

        $order = $this->createOrder(
            $user,
            OrderStatus::PENDING,
            PaymentStatus::PAID
        );

        $orderService = app(OrderService::class);

        // Act
        $orderService->cancelOrder($user, $order->id);

        // Assert
        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => OrderStatus::CANCELLED->value,
            'payment_status' => PaymentStatus::PAID->value,
        ]);
    }

    private function assertOrderCanBeCancelled(OrderStatus $status): void
    {
        // Arrange
        $user = User::factory()->create();

        $order = $this->createOrder($user, $status);

        $orderService = app(OrderService::class);

        // Act
        $cancelledOrder = $orderService->cancelOrder($user, $order->id);

        // Assert
        $this->assertSame(OrderStatus::CANCELLED, $cancelledOrder->status);

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => OrderStatus::CANCELLED->value,
        ]);
    }

    private function assertOrderCannotBeCancelled(OrderStatus $status): void
    {
        // Arrange
        $user = User::factory()->create();

        $order = $this->createOrder($user, $status);

        $orderService = app(OrderService::class);

        // Assert
        try {
            $orderService->cancelOrder($user, $order->id);
            $this->fail('Expected an exception for a non-cancellable order.');
        } catch (\InvalidArgumentException $exception) {
            $this->assertSame(
                'This order cannot be cancelled.',
                $exception->getMessage()
            );
        }

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => $status->value,
        ]);
    }

    private function createOrder(
        User $user,
        OrderStatus $status,
        PaymentStatus $paymentStatus = PaymentStatus::PENDING
    ): Order {
        return Order::factory()->create([
            'user_id' => $user->id,
            'status' => $status,
            'payment_status' => $paymentStatus,
        ]);
    }
}

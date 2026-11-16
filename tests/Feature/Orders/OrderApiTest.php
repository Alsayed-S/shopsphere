<?php

namespace Tests\Feature\Orders;

use App\Models\User;
use App\Modules\Catalog\Models\Product;
use App\Modules\Orders\Models\Order;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Modules\Orders\Enums\OrderStatus;
use App\Modules\Orders\Enums\PaymentStatus;
use App\Modules\Orders\Models\OrderItem;
use Tests\TestCase;

class OrderApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_create_an_order(): void
    {
        $response = $this->postJson('/api/orders', [
            'shipping_name' => 'Test Customer',
            'shipping_phone' => '01234567890',
            'shipping_address' => 'Cairo, Egypt',
        ]);

        $response->assertUnauthorized();

        $this->assertDatabaseCount('orders', 0);
    }


    public function test_authenticated_user_can_create_an_order(): void
    {
        // Arrange
        $user = User::factory()->create();

        $product = Product::factory()->published()->create([
            'price' => '19.99',
        ]);

        $cart = $user->cart()->create();

        $cart->items()->create([
            'product_id' => $product->id,
            'quantity' => 2,
        ]);

        // Act
        $response = $this->actingAs($user)
            ->postJson('/api/orders', [
                'shipping_name' => 'Test Customer',
                'shipping_phone' => '01234567890',
                'shipping_address' => 'Cairo, Egypt',
            ]);

        // Assert
        $response->assertCreated()
            ->assertJsonPath('data.status', 'pending')
            ->assertJsonPath('data.payment_status', 'pending')
            ->assertJsonPath('data.subtotal', '39.98')
            ->assertJsonPath('data.total', '39.98');

        $this->assertDatabaseCount('orders', 1);
        $this->assertDatabaseCount('order_items', 1);
        $this->assertDatabaseCount('cart_items', 0);
    }


    public function test_order_creation_requires_shipping_information(): void
    {
        // Arrange
        $user = User::factory()->create();

        // Act
        $response = $this->actingAs($user)
            ->postJson('/api/orders', []);

        // Assert
        $response->assertUnprocessable()
            ->assertJsonValidationErrors([
                'shipping_name',
                'shipping_phone',
                'shipping_address',
            ]);

        $this->assertDatabaseCount('orders', 0);
    }


    public function test_user_cannot_create_an_order_from_an_empty_cart(): void
    {
        // Arrange
        $user = User::factory()->create();

        // Act
        $response = $this->actingAs($user)
            ->postJson('/api/orders', [
                'shipping_name' => 'Test Customer',
                'shipping_phone' => '01234567890',
                'shipping_address' => 'Cairo, Egypt',
            ]);

        // Assert
        $response->assertUnprocessable()
            ->assertJsonPath(
                'message',
                'Cannot create an order from an empty cart.'
            );

        $this->assertDatabaseCount('orders', 0);
    }


    public function test_user_cannot_order_unpublished_products(): void
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

        // Act
        $response = $this->actingAs($user)
            ->postJson('/api/orders', [
                'shipping_name' => 'Test Customer',
                'shipping_phone' => '01234567890',
                'shipping_address' => 'Cairo, Egypt',
            ]);

        // Assert
        $response->assertUnprocessable()
            ->assertJsonPath(
                'message',
                'One or more products are unavailable.'
            );

        $this->assertDatabaseCount('orders', 0);
        $this->assertDatabaseCount('cart_items', 1);
    }


    public function test_user_can_only_view_their_own_orders(): void
    {
        // Arrange
        $user = User::factory()->create();
        $anotherUser = User::factory()->create();

        $userOrder = Order::factory()->create([
            'user_id' => $user->id,
        ]);

        Order::factory()->create([
            'user_id' => $anotherUser->id,
        ]);

        // Act
        $response = $this->actingAs($user)
            ->getJson('/api/orders');
        // Assert
        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $userOrder->id);
    }


    public function test_user_without_orders_receives_empty_list(): void
    {
        // Arrange
        $user = User::factory()->create();

        // Act
        $response = $this->actingAs($user)
            ->getJson('/api/orders');

        // Assert
        $response->assertOk()
            ->assertJsonCount(0, 'data');
    }


    public function test_user_orders_are_paginated(): void
    {
        // Arrange
        $user = User::factory()->create();

        Order::factory()->count(12)->create([
            'user_id' => $user->id,
        ]);

        // Act
        $response = $this->actingAs($user)
            ->getJson('/api/orders?per_page=5');

        // Assert
        $response->assertOk()
            ->assertJsonCount(5, 'data')
            ->assertJsonPath('pagination.current_page', 1)
            ->assertJsonPath('pagination.last_page', 3)
            ->assertJsonPath('pagination.per_page', 5)
            ->assertJsonPath('pagination.total', 12);
    }


    public function test_guest_cannot_view_orders(): void
    {
        // Act
        $response = $this->getJson('/api/orders');

        // Assert
        $response->assertUnauthorized();
    }

    public function test_per_page_must_be_at_least_one(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/orders?per_page=0');

        $response->assertUnprocessable()
            ->assertJsonValidationErrors('per_page');
    }

    public function test_per_page_cannot_exceed_one_hundred(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/orders?per_page=101');

        $response->assertUnprocessable()
            ->assertJsonValidationErrors('per_page');
    }

    public function test_per_page_must_be_an_integer(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/orders?per_page=abc');

        $response->assertUnprocessable()
            ->assertJsonValidationErrors('per_page');
    }

    public function test_orders_use_ten_items_per_page_by_default(): void
    {
        $user = User::factory()->create();

        Order::factory()->count(12)->create([
            'user_id' => $user->id,
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/orders');

        $response->assertOk()
            ->assertJsonPath('pagination.per_page', 10)
            ->assertJsonPath('pagination.total', 12)
            ->assertJsonCount(10, 'data');
    }

    public function test_guest_cannot_view_order_details(): void
    {
        $order = Order::factory()->create();

        $response = $this->getJson("/api/orders/{$order->id}");

        $response->assertUnauthorized();
    }

    public function test_user_can_view_their_own_order_details(): void
    {
        // Arrange
        $user = User::factory()->create();

        $order = Order::factory()->create([
            'user_id' => $user->id,
            'shipping_name' => 'Test Customer',
            'shipping_phone' => '01234567890',
            'shipping_address' => 'Cairo, Egypt',
        ]);

        $order->items()->create([
            'product_id' => null,
            'product_name' => 'Test Product',
            'product_slug' => 'test-product',
            'unit_price' => '19.99',
            'quantity' => 2,
            'subtotal' => '39.98',
        ]);

        // Act
        $response = $this->actingAs($user)
            ->getJson("/api/orders/{$order->id}");

        // Assert
        $response->assertOk()
            ->assertJsonPath('data.id', $order->id)
            ->assertJsonPath('data.shipping.name', 'Test Customer')
            ->assertJsonPath('data.shipping.phone', '01234567890')
            ->assertJsonPath('data.shipping.address', 'Cairo, Egypt')
            ->assertJsonPath('data.status', 'pending')
            ->assertJsonPath('data.items.0.product_name', 'Test Product')
            ->assertJsonPath('data.items.0.quantity', 2)
            ->assertJsonPath('data.items.0.subtotal', '39.98');
    }

    public function test_user_cannot_view_another_users_order_details(): void
    {
        // Arrange
        $user = User::factory()->create();
        $anotherUser = User::factory()->create();

        $order = Order::factory()->create([
            'user_id' => $anotherUser->id,
        ]);

        // Act
        $response = $this->actingAs($user)
            ->getJson("/api/orders/{$order->id}");

        // Assert
        $response->assertNotFound();
    }

    public function test_user_cannot_view_nonexistent_order_details(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->getJson('/api/orders/999999');

        $response->assertNotFound();
    }

    public function test_guest_cannot_cancel_an_order(): void
    {
        $order = Order::factory()->create();

        $response = $this->postJson("/api/orders/{$order->id}/cancel");

        $response->assertUnauthorized();
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

    public function test_user_cannot_cancel_another_users_order(): void
    {
        // Arrange
        $user = User::factory()->create();
        $anotherUser = User::factory()->create();

        $order = Order::factory()->create([
            'user_id' => $anotherUser->id,
            'status' => OrderStatus::PENDING,
        ]);

        // Act
        $response = $this->actingAs($user)
            ->postJson("/api/orders/{$order->id}/cancel");

        // Assert
        $response->assertNotFound();

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => OrderStatus::PENDING->value,
        ]);
    }

    public function test_cancelling_an_order_does_not_change_its_payment_status(): void
    {
        // Arrange
        $user = User::factory()->create();

        $order = Order::factory()->create([
            'user_id' => $user->id,
            'status' => OrderStatus::PENDING,
            'payment_status' => PaymentStatus::PAID,
        ]);

        // Act
        $response = $this->actingAs($user)
            ->postJson("/api/orders/{$order->id}/cancel");

        // Assert
        $response->assertOk()
            ->assertJsonPath('data.status', 'cancelled');

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

        $order = Order::factory()->create([
            'user_id' => $user->id,
            'status' => $status,
        ]);

        // Act
        $response = $this->actingAs($user)
            ->postJson("/api/orders/{$order->id}/cancel");

        // Assert
        $response->assertOk()
            ->assertJsonPath('data.status', 'cancelled');

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => OrderStatus::CANCELLED->value,
        ]);
    }

    private function assertOrderCannotBeCancelled(OrderStatus $status): void
    {
        // Arrange
        $user = User::factory()->create();

        $order = Order::factory()->create([
            'user_id' => $user->id,
            'status' => $status,
        ]);

        // Act
        $response = $this->actingAs($user)
            ->postJson("/api/orders/{$order->id}/cancel");

        // Assert
        $response->assertUnprocessable();

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => $status->value,
        ]);
    }
}

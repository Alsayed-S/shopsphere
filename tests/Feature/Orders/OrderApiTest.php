<?php

namespace Tests\Feature\Orders;

use App\Models\User;
use App\Modules\Catalog\Models\Product;
use App\Modules\Orders\Models\Order;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
}

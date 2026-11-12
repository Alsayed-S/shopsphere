<?php

namespace Tests\Feature\Cart;

use App\Models\User;
use App\Modules\Cart\Models\Cart;
use App\Modules\Cart\Models\CartItem;
use App\Modules\Catalog\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CartApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_access_cart_endpoints(): void
    {
        $this->getJson('/api/cart')
            ->assertUnauthorized();

        $this->postJson('/api/cart/items')
            ->assertUnauthorized();

        $this->patchJson('/api/cart/items/1')
            ->assertUnauthorized();

        $this->deleteJson('/api/cart/items/1')
            ->assertUnauthorized();

        $this->deleteJson('/api/cart')
            ->assertUnauthorized();
    }

    public function test_user_can_view_empty_cart(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->getJson('/api/cart');

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.items_count', 0)
            ->assertJsonPath('data.total_quantity', 0)
            ->assertJsonPath('data.subtotal', '0.00')
            ->assertJsonPath('data.total', '0.00');

        $this->assertDatabaseHas('carts', [
            'user_id' => $user->id,
        ]);
    }

    public function test_user_can_add_product_to_cart(): void
    {
        $user = User::factory()->create();

        $product = Product::factory()->published()->create([
            'price' => '100.00',
        ]);

        $response = $this->actingAs($user)
            ->postJson('/api/cart/items', [
                'product_id' => $product->id,
                'quantity' => 2,
            ]);

        $response->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.items_count', 1)
            ->assertJsonPath('data.total_quantity', 2)
            ->assertJsonPath('data.subtotal', '200.00');

        $this->assertDatabaseHas('cart_items', [
            'product_id' => $product->id,
            'quantity' => 2,
        ]);
    }

    public function test_adding_same_product_increases_quantity(): void
    {
        $user = User::factory()->create();

        $product = Product::factory()->published()->create();

        $this->actingAs($user)
            ->postJson('/api/cart/items', [
                'product_id' => $product->id,
                'quantity' => 2,
            ]);

        $response = $this->postJson('/api/cart/items', [
            'product_id' => $product->id,
            'quantity' => 3,
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.items_count', 1)
            ->assertJsonPath('data.total_quantity', 5);

        $this->assertDatabaseHas('cart_items', [
            'product_id' => $product->id,
            'quantity' => 5,
        ]);

        $this->assertDatabaseCount('cart_items', 1);
    }

    public function test_user_cannot_add_draft_product(): void
    {
        $user = User::factory()->create();

        $product = Product::factory()->create([
            'status' => 'draft',
        ]);

        $response = $this->actingAs($user)
            ->postJson('/api/cart/items', [
                'product_id' => $product->id,
                'quantity' => 1,
            ]);

        $response->assertUnprocessable();

        $this->assertDatabaseCount('carts', 0);
        $this->assertDatabaseCount('cart_items', 0);
    }

    public function test_add_product_requires_valid_data(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->postJson('/api/cart/items', [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'product_id',
            ]);

        $product = Product::factory()->published()->create();

        $this->postJson('/api/cart/items', [
            'product_id' => $product->id,
            'quantity' => 0,
        ])->assertUnprocessable()
            ->assertJsonValidationErrors([
                'quantity',
            ]);
    }

    public function test_user_can_update_cart_item_quantity(): void
    {
        $user = User::factory()->create();

        $product = Product::factory()->published()->create([
            'price' => '50.00',
        ]);

        $cartItem = $this->createCartItem(
            $user,
            $product,
            2
        );

        $response = $this->actingAs($user)
            ->patchJson("/api/cart/items/{$cartItem->id}", [
                'quantity' => 4,
            ]);

        $response->assertOk()
            ->assertJsonPath('data.total_quantity', 4)
            ->assertJsonPath('data.subtotal', '200.00');

        $this->assertDatabaseHas('cart_items', [
            'id' => $cartItem->id,
            'quantity' => 4,
        ]);
    }

    public function test_user_cannot_update_another_users_cart_item(): void
    {
        $owner = User::factory()->create();
        $anotherUser = User::factory()->create();

        $product = Product::factory()->published()->create();

        $cartItem = $this->createCartItem(
            $owner,
            $product
        );

        $this->actingAs($anotherUser)
            ->patchJson("/api/cart/items/{$cartItem->id}", [
                'quantity' => 5,
            ])
            ->assertNotFound();

        $this->assertDatabaseHas('cart_items', [
            'id' => $cartItem->id,
            'quantity' => 1,
        ]);
    }

    public function test_update_quantity_requires_positive_integer(): void
    {
        $user = User::factory()->create();

        $product = Product::factory()->published()->create();

        $cartItem = $this->createCartItem(
            $user,
            $product
        );

        $this->actingAs($user)
            ->patchJson("/api/cart/items/{$cartItem->id}", [
                'quantity' => 0,
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'quantity',
            ]);
    }

    public function test_user_can_remove_cart_item(): void
    {
        $user = User::factory()->create();

        $product = Product::factory()->published()->create();

        $cartItem = $this->createCartItem(
            $user,
            $product
        );

        $response = $this->actingAs($user)
            ->deleteJson("/api/cart/items/{$cartItem->id}");

        $response->assertOk()
            ->assertJsonPath('data.items_count', 0)
            ->assertJsonPath('data.total_quantity', 0);

        $this->assertDatabaseMissing('cart_items', [
            'id' => $cartItem->id,
        ]);

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
        ]);
    }

    public function test_user_cannot_remove_another_users_cart_item(): void
    {
        $owner = User::factory()->create();
        $anotherUser = User::factory()->create();

        $product = Product::factory()->published()->create();

        $cartItem = $this->createCartItem(
            $owner,
            $product
        );

        $this->actingAs($anotherUser)
            ->deleteJson("/api/cart/items/{$cartItem->id}")
            ->assertNotFound();

        $this->assertDatabaseHas('cart_items', [
            'id' => $cartItem->id,
        ]);
    }

    public function test_user_can_clear_cart(): void
    {
        $user = User::factory()->create();

        $product1 = Product::factory()->published()->create();
        $product2 = Product::factory()->published()->create();

        $this->createCartItem($user, $product1, 2);
        $this->createCartItem($user, $product2, 3);

        $cartId = $user->cart->id;

        $response = $this->actingAs($user)
            ->deleteJson('/api/cart');

        $response->assertOk()
            ->assertJsonPath('data.items_count', 0)
            ->assertJsonPath('data.total_quantity', 0)
            ->assertJsonPath('data.subtotal', '0.00');

        $this->assertDatabaseHas('carts', [
            'id' => $cartId,
            'user_id' => $user->id,
        ]);

        $this->assertDatabaseCount('cart_items', 0);

        $this->assertDatabaseHas('products', [
            'id' => $product1->id,
        ]);

        $this->assertDatabaseHas('products', [
            'id' => $product2->id,
        ]);
    }

    public function test_cart_total_is_calculated_correctly(): void
    {
        $user = User::factory()->create();

        $product1 = Product::factory()->published()->create([
            'price' => '100.00',
        ]);

        $product2 = Product::factory()->published()->create([
            'price' => '50.00',
        ]);

        $this->createCartItem($user, $product1, 2);
        $this->createCartItem($user, $product2, 3);

        $response = $this->actingAs($user)
            ->getJson('/api/cart');

        $response->assertOk()
            ->assertJsonPath('data.items_count', 2)
            ->assertJsonPath('data.total_quantity', 5)
            ->assertJsonPath('data.subtotal', '350.00')
            ->assertJsonPath('data.total', '350.00');
    }

    private function createCartItem(
        User $user,
        Product $product,
        int $quantity = 1
    ): CartItem {
        $cart = Cart::firstOrCreate([
            'user_id' => $user->id,
        ]);

        return $cart->items()->create([
            'product_id' => $product->id,
            'quantity' => $quantity,
        ]);
    }
}

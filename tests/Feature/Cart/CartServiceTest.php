<?php

namespace Tests\Feature\Cart;

use App\Models\User;
use App\Modules\Cart\Models\Cart;
use App\Modules\Cart\Models\CartItem;
use App\Modules\Cart\Services\CartService;
use App\Modules\Catalog\Models\Product;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Tests\TestCase;

class CartServiceTest extends TestCase
{
    use RefreshDatabase;

    /*
    |--------------------------------------------------------------------------
    | Get Or Create Cart Tests
    |--------------------------------------------------------------------------
    */

    public function test_it_creates_a_cart_for_user(): void
    {
        $user = User::factory()->create();

        $cartService = new CartService;

        $cart = $cartService->getOrCreateCart($user);

        $this->assertInstanceOf(Cart::class, $cart);

        $this->assertDatabaseHas('carts', [
            'id' => $cart->id,
            'user_id' => $user->id,
        ]);

        $this->assertDatabaseCount('carts', 1);
    }

    public function test_it_returns_existing_cart_without_creating_duplicate(): void
    {
        $user = User::factory()->create();

        $cartService = new CartService;

        $firstCart = $cartService->getOrCreateCart($user);
        $secondCart = $cartService->getOrCreateCart($user);

        $this->assertEquals($firstCart->id, $secondCart->id);

        $this->assertDatabaseCount('carts', 1);
    }

    /*
    |--------------------------------------------------------------------------
    | Add Product Tests
    |--------------------------------------------------------------------------
    */

    public function test_it_can_add_product_to_cart(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->published()->create();

        $cartService = new CartService;

        $cartItem = $cartService->addProduct($user, $product);

        $this->assertInstanceOf(CartItem::class, $cartItem);

        $this->assertDatabaseHas('cart_items', [
            'cart_id' => $cartItem->cart_id,
            'product_id' => $product->id,
            'quantity' => 1,
        ]);

        $this->assertDatabaseCount('cart_items', 1);
    }

    public function test_it_increases_quantity_when_adding_existing_product(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->published()->create();

        $cartService = new CartService;

        $firstItem = $cartService->addProduct(
            $user,
            $product,
            1
        );

        $secondItem = $cartService->addProduct(
            $user,
            $product,
            2
        );

        $this->assertEquals($firstItem->id, $secondItem->id);

        $this->assertDatabaseHas('cart_items', [
            'id' => $firstItem->id,
            'quantity' => 3,
        ]);

        $this->assertDatabaseCount('cart_items', 1);
    }

    public function test_it_can_add_different_products_to_same_cart(): void
    {
        $user = User::factory()->create();

        $firstProduct = Product::factory()->published()->create();
        $secondProduct = Product::factory()->published()->create();

        $cartService = new CartService;

        $firstItem = $cartService->addProduct($user, $firstProduct);
        $secondItem = $cartService->addProduct($user, $secondProduct);

        $this->assertEquals(
            $firstItem->cart_id,
            $secondItem->cart_id
        );

        $this->assertDatabaseCount('cart_items', 2);
        $this->assertDatabaseCount('carts', 1);
    }

    public function test_different_users_have_separate_carts(): void
    {
        $firstUser = User::factory()->create();
        $secondUser = User::factory()->create();

        $product = Product::factory()->published()->create();

        $cartService = new CartService;

        $firstItem = $cartService->addProduct($firstUser, $product);
        $secondItem = $cartService->addProduct($secondUser, $product);

        $this->assertNotEquals(
            $firstItem->cart_id,
            $secondItem->cart_id
        );

        $this->assertDatabaseCount('carts', 2);
        $this->assertDatabaseCount('cart_items', 2);
    }

    public function test_it_cannot_add_product_with_zero_quantity(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->published()->create();

        $this->expectException(InvalidArgumentException::class);

        (new CartService)->addProduct($user, $product, 0);
    }

    public function test_it_cannot_add_product_with_negative_quantity(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->published()->create();

        $this->expectException(InvalidArgumentException::class);

        (new CartService)->addProduct($user, $product, -1);
    }

    public function test_it_cannot_add_draft_product_to_cart(): void
    {
        $user = User::factory()->create();

        $product = Product::factory()->create([
            'status' => 'draft',
        ]);

        try {
            (new CartService)->addProduct($user, $product);

            $this->fail(
                'Expected InvalidArgumentException was not thrown.'
            );
        } catch (InvalidArgumentException $exception) {
            $this->assertSame(
                'Only published products can be added to the cart.',
                $exception->getMessage()
            );
        }

        $this->assertDatabaseCount('carts', 0);
        $this->assertDatabaseCount('cart_items', 0);
    }

    /*
    |--------------------------------------------------------------------------
    | Update Quantity Tests
    |--------------------------------------------------------------------------
    */

    public function test_it_can_update_cart_item_quantity(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->published()->create();

        $cartService = new CartService;

        $cartItem = $cartService->addProduct($user, $product, 2);

        $updatedItem = $cartService->updateQuantity(
            $user,
            $cartItem->id,
            5
        );

        $this->assertEquals(5, $updatedItem->quantity);

        $this->assertDatabaseHas('cart_items', [
            'id' => $cartItem->id,
            'quantity' => 5,
        ]);
    }

    public function test_it_cannot_update_quantity_to_zero(): void
    {
        $user = User::factory()->create();

        $this->expectException(InvalidArgumentException::class);

        (new CartService)->updateQuantity($user, 1, 0);
    }

    public function test_it_cannot_update_quantity_to_negative_value(): void
    {
        $user = User::factory()->create();

        $this->expectException(InvalidArgumentException::class);

        (new CartService)->updateQuantity($user, 1, -1);
    }

    public function test_user_cannot_update_another_users_cart_item(): void
    {
        $firstUser = User::factory()->create();
        $secondUser = User::factory()->create();

        $product = Product::factory()->published()->create();

        $cartService = new CartService;

        $cartItem = $cartService->addProduct(
            $firstUser,
            $product
        );

        $cartService->getOrCreateCart($secondUser);

        $this->expectException(ModelNotFoundException::class);

        $cartService->updateQuantity(
            $secondUser,
            $cartItem->id,
            5
        );
    }

    public function test_it_cannot_update_nonexistent_cart_item(): void
    {
        $user = User::factory()->create();

        $cartService = new CartService;

        $cartService->getOrCreateCart($user);

        $this->expectException(ModelNotFoundException::class);

        $cartService->updateQuantity($user, 999999, 5);
    }

    /*
    |--------------------------------------------------------------------------
    | Remove Product Tests
    |--------------------------------------------------------------------------
    */

    public function test_it_can_remove_product_from_cart_without_deleting_product(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->published()->create();

        $cartService = new CartService;

        $cartItem = $cartService->addProduct($user, $product);

        $cartService->removeProduct($user, $cartItem->id);

        $this->assertDatabaseMissing('cart_items', [
            'id' => $cartItem->id,
        ]);

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
        ]);

        $this->assertDatabaseCount('carts', 1);
    }

    public function test_user_cannot_remove_another_users_cart_item(): void
    {
        $firstUser = User::factory()->create();
        $secondUser = User::factory()->create();

        $product = Product::factory()->published()->create();

        $cartService = new CartService;

        $cartItem = $cartService->addProduct($firstUser, $product);

        $cartService->getOrCreateCart($secondUser);

        $this->expectException(ModelNotFoundException::class);

        $cartService->removeProduct($secondUser, $cartItem->id);
    }

    public function test_it_cannot_remove_nonexistent_cart_item(): void
    {
        $user = User::factory()->create();

        $cartService = new CartService;

        $cartService->getOrCreateCart($user);

        $this->expectException(ModelNotFoundException::class);

        $cartService->removeProduct($user, 999999);
    }

    /*
    |--------------------------------------------------------------------------
    | Clear Cart Tests
    |--------------------------------------------------------------------------
    */

    public function test_it_can_clear_cart_without_deleting_cart_or_products(): void
    {
        $user = User::factory()->create();

        $firstProduct = Product::factory()->published()->create();
        $secondProduct = Product::factory()->published()->create();

        $cartService = new CartService;

        $cartService->addProduct($user, $firstProduct);
        $cartService->addProduct($user, $secondProduct);

        $cart = $user->cart()->firstOrFail();

        $cartService->clearCart($user);

        $this->assertDatabaseHas('carts', [
            'id' => $cart->id,
            'user_id' => $user->id,
        ]);

        $this->assertDatabaseCount('cart_items', 0);

        $this->assertDatabaseHas('products', [
            'id' => $firstProduct->id,
        ]);

        $this->assertDatabaseHas('products', [
            'id' => $secondProduct->id,
        ]);

        $this->assertDatabaseCount('carts', 1);
    }

    public function test_it_can_clear_cart_when_user_has_no_cart(): void
    {
        $user = User::factory()->create();

        $cartService = new CartService;

        $cartService->clearCart($user);

        $this->assertDatabaseCount('carts', 0);
        $this->assertDatabaseCount('cart_items', 0);
    }
}

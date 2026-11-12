<?php

namespace App\Modules\Cart\Services;

use App\Models\User;
use App\Modules\Cart\Models\Cart;
use App\Modules\Cart\Models\CartItem;
use App\Modules\Catalog\Models\Product;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class CartService
{
    public function getOrCreateCart(User $user): Cart
    {
        return $user->cart()->firstOrCreate();
    }

    public function addProduct(
        User $user,
        Product $product,
        int $quantity = 1
    ): CartItem {
        if ($quantity < 1) {
            throw new InvalidArgumentException(
                'Quantity must be at least 1.'
            );
        }

        if ($product->status !== 'published') {
            throw new InvalidArgumentException(
                'Only published products can be added to the cart.'
            );
        }

        return DB::transaction(function () use (
            $user,
            $product,
            $quantity
        ) {
            $this->getOrCreateCart($user);

            $cart = $user->cart()
                ->lockForUpdate()
                ->firstOrFail();

            $cartItem = $cart->items()->firstOrNew([
                'product_id' => $product->id,
            ]);

            $cartItem->quantity =
                ($cartItem->exists ? $cartItem->quantity : 0) + $quantity;

            $cartItem->save();

            return $cartItem->load('product');
        });
    }

    public function updateQuantity(
        User $user,
        int $cartItemId,
        int $quantity
    ): CartItem {
        if ($quantity < 1) {
            throw new InvalidArgumentException(
                'Quantity must be at least 1.'
            );
        }

        return DB::transaction(function () use (
            $user,
            $cartItemId,
            $quantity
        ) {
            $cart = $user->cart()
                ->lockForUpdate()
                ->firstOrFail();

            $cartItem = $cart->items()->findOrFail($cartItemId);

            $cartItem->quantity = $quantity;
            $cartItem->save();

            return $cartItem->load('product');
        });
    }

    public function removeProduct(User $user, int $cartItemId): void
    {
        DB::transaction(function () use ($user, $cartItemId) {
            $cart = $user->cart()
                ->lockForUpdate()
                ->firstOrFail();

            $cartItem = $cart->items()->findOrFail($cartItemId);

            $cartItem->delete();
        });
    }

    public function clearCart(User $user): void
    {
        DB::transaction(function () use ($user) {
            $cart = $user->cart()
                ->lockForUpdate()
                ->first();

            if (! $cart) {
                return;
            }

            $cart->items()->delete();
        });
    }
}

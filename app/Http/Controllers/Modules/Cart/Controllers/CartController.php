<?php

namespace App\Http\Controllers\Modules\Cart\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCartItemRequest;
use App\Http\Requests\UpdateCartItemRequest;
use App\Http\Resources\CartResource;
use App\Modules\Cart\Services\CartService;
use App\Modules\Catalog\Models\Product;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CartController extends Controller
{
    use ApiResponse;

    public function __construct(
        private CartService $cartService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $cart = $this->cartService->getOrCreateCart(
            $request->user()
        );

        $cart->load('items.product');

        return $this->successResponse(
            'Cart retrieved successfully.',
            (new CartResource($cart))->resolve($request)
        );
    }

    public function addItem(StoreCartItemRequest $request): JsonResponse
    {
        $data = $request->validated();

        $user = $request->user();

        $product = Product::findOrFail($data['product_id']);

        $this->cartService->addProduct(
            $user,
            $product,
            $data['quantity'] ?? 1
        );

        $cart = $this->cartService->getOrCreateCart($user);

        $cart->load('items.product');

        return $this->successResponse(
            'Product added to cart successfully.',
            (new CartResource($cart))->resolve($request),
            201
        );
    }

    public function updateItem(
        UpdateCartItemRequest $request,
        int $itemId
    ): JsonResponse {
        $this->cartService->updateQuantity(
            $request->user(),
            $itemId,
            $request->validated('quantity')
        );

        $cart = $this->cartService->getOrCreateCart(
            $request->user()
        );

        $cart->load('items.product');

        return $this->successResponse(
            'Cart item updated successfully.',
            (new CartResource($cart))->resolve($request)
        );
    }

    public function removeItem(Request $request, int $itemId): JsonResponse
    {
        $this->cartService->removeProduct(
            $request->user(),
            $itemId
        );

        $cart = $this->cartService->getOrCreateCart(
            $request->user()
        );

        $cart->load('items.product');

        return $this->successResponse(
            'Cart item removed successfully.',
            (new CartResource($cart))->resolve($request)
        );
    }

    public function clear(Request $request): JsonResponse
    {
        $this->cartService->clearCart($request->user());

        $cart = $this->cartService->getOrCreateCart(
            $request->user()
        );

        $cart->load('items.product');

        return $this->successResponse(
            'Cart cleared successfully.',
            (new CartResource($cart))->resolve($request)
        );
    }
}

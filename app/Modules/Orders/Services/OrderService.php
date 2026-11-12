<?php

namespace App\Modules\Orders\Services;

use App\Models\User;
use App\Modules\Orders\Enums\OrderStatus;
use App\Modules\Orders\Enums\PaymentStatus;
use App\Modules\Orders\Models\Order;
use App\Support\Money;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;

class OrderService
{
    public function createFromCart(User $user, array $shippingData): Order
    {
        return DB::transaction(function () use ($user, $shippingData) {
            $cart = $user->cart()
                ->with('items.product')
                ->lockForUpdate()
                ->first();

            if (! $cart || $cart->items->isEmpty()) {
                throw new InvalidArgumentException(
                    'Cannot create an order from an empty cart.'
                );
            }

            foreach ($cart->items as $item) {
                if (! $item->product || $item->product->status !== 'published') {
                    throw new InvalidArgumentException(
                        'One or more products are unavailable.'
                    );
                }
            }

            $subtotalInMinorUnits = 0;

            foreach ($cart->items as $item) {
                $unitPrice = Money::toMinorUnits($item->product->price);

                $subtotalInMinorUnits += $unitPrice * $item->quantity;
            }

            $subtotal = Money::format($subtotalInMinorUnits);

            $order = $user->orders()->create([
                'order_number' => 'ORD-' . strtoupper(Str::random(12)),
                'status' => OrderStatus::PENDING,
                'payment_status' => PaymentStatus::PENDING,
                'shipping_name' => $shippingData['shipping_name'],
                'shipping_phone' => $shippingData['shipping_phone'],
                'shipping_address' => $shippingData['shipping_address'],
                'subtotal' => $subtotal,
                'total' => $subtotal,
            ]);

            foreach ($cart->items as $item) {
                $unitPrice = Money::toMinorUnits($item->product->price);
                $itemSubtotal = $unitPrice * $item->quantity;

                $order->items()->create([
                    'product_id' => $item->product_id,
                    'product_name' => $item->product->name,
                    'product_slug' => $item->product->slug,
                    'unit_price' => Money::format($unitPrice),
                    'quantity' => $item->quantity,
                    'subtotal' => Money::format($itemSubtotal),
                ]);
            }

            $cart->items()->delete();

            return $order->load('items');
        });
    }

    public function getUserOrders(User $user, int $perPage = 10): LengthAwarePaginator
    {
        return $user->orders()
            ->with('items')
            ->latest()
            ->paginate($perPage);
    }
}

<?php

namespace App\Http\Resources;

use App\Modules\Cart\Models\CartItem;
use App\Support\Money;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CartResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $items = $this->items;

        $subtotal = $items->sum(
            function (CartItem $item): int {
                $unitPrice = Money::toMinorUnits(
                    (string) $item->product->price
                );

                return $unitPrice * (int) $item->quantity;
            }
        );

        return [
            'id' => $this->id,

            'items' => CartItemResource::collection($items),

            'items_count' => $items->count(),

            'total_quantity' => (int) $items->sum('quantity'),

            'subtotal' => Money::format($subtotal),

            'total' => Money::format($subtotal),
        ];
    }
}

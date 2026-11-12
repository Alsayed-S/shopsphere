<?php

namespace App\Http\Resources;

use App\Support\Money;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CartItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $product = $this->product;

        $unitPrice = Money::toMinorUnits(
            (string) $product->price
        );

        $quantity = (int) $this->quantity;

        return [
            'id' => $this->id,

            'product' => [
                'id' => $product->id,
                'name' => $product->name,
                'slug' => $product->slug,
            ],

            'quantity' => $quantity,

            'unit_price' => Money::format($unitPrice),

            'subtotal' => Money::format(
                $unitPrice * $quantity
            ),
        ];
    }
}

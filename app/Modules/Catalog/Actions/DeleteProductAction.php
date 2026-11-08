<?php

namespace App\Modules\Catalog\Actions;

use App\Modules\Catalog\Models\Product;

class DeleteProductAction
{
 
    /**
     * Delete a product by ID.
     */
    public function execute(int $productId): void
    {
        $product = Product::query()->findOrFail($productId);
        $product->delete();
    }
}

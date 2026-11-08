<?php

namespace App\Modules\Catalog\Actions;

use App\Modules\Catalog\Models\Product;

class ShowProductAction
{
    

    /**
     * Show a product by ID.
     */
    public function execute(int $productId)
    {
        return Product::findOrFail($productId);
    }
}

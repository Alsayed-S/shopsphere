<?php

namespace App\Modules\Catalog\Actions;

use App\Modules\Catalog\Models\Product;

class UpdateProductAction
{
    
    /**
     * Update a product by ID.
     */
   
    public function execute(int $productId, array $data)
    {
       $product = Product::query()->findOrFail($productId);
       $product->update($data);
       return $product->refresh();
    }
   
}

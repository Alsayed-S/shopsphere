<?php

namespace App\Modules\Catalog\Actions;

use App\Modules\Catalog\Models\Product;



class CreateProductAction
{
    

      /**
     * Create a new product.
     */
    public function execute(array $data):product
    {
        return Product::query()->create($data);
    }
}

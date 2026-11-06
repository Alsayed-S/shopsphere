<?php

namespace App\Modules\Catalog\Actions;

use App\Modules\Catalog\Models\Product;



class CreateProductAction
{
    /**
     * Create a new class instance.
     */
    public function __construct()
    {
        //
    }

      /**
     * Create a new product.
     */
    public function execute(array $data)
    {
        return Product::query()->create($data);
    }
}

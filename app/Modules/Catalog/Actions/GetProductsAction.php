<?php

namespace App\Modules\Catalog\Actions;

use App\Modules\Catalog\Models\Product;



class GetProductsAction
{
    

      /**
     * Get paginated products.
     */
    public function execute(int $perPage = 15)
    {
        return Product::query()
                        ->latest()
                        ->paginate($perPage);
    }
}

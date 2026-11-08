<?php

namespace App\Http\Controllers\Modules\Catalog\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Catalog\ProductIndexRequest;
use App\Http\Requests\StoreProductRequest;
use App\Http\Requests\UpdateProductRequest;
use App\Http\Resources\ProductResource;
use App\Modules\Catalog\Actions\CreateProductAction;
use App\Modules\Catalog\Actions\DeleteProductAction;
use App\Modules\Catalog\Actions\GetProductsAction;
use App\Modules\Catalog\Actions\ShowProductAction;
use App\Modules\Catalog\Actions\UpdateProductAction;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    use ApiResponse;
    public function store(StoreProductRequest $request, CreateProductAction $createProductAction): JsonResponse
    {
        $validatedData = $request->validated();

        $product = $createProductAction->execute($validatedData);

        return $this->createdResponse('Product created successfully.',new ProductResource($product),);

    }

    public function index(ProductIndexRequest  $request,
    GetProductsAction $getProductsAction): JsonResponse
    {
        $perPage = $request->validated('per_page', 10);

        $products = $getProductsAction->execute($perPage);

        return ProductResource::collection($products)->additional([
            'success' => true,
            'message' => 'Products retrieved successfully.',
        ])->response();
    }

    public function show( int $productId,
    ShowProductAction $showProductAction): JsonResponse
    {
        $product = $showProductAction->execute($productId);

        return $this->successResponse(
            'Product retrieved successfully.',
             new ProductResource($product)
            );
    }

    public function update(int $productId, UpdateProductRequest  $request, UpdateProductAction $updateProductAction): JsonResponse
    {
        $validatedData = $request->validated();

        $product = $updateProductAction->execute($productId, $validatedData);

        return $this->successResponse(
            'Product updated successfully.',
            new ProductResource($product)
        );
    }

    public function destroy(int $productId, DeleteProductAction $deleteProductAction): JsonResponse
    {
        $deleteProductAction->execute($productId);

        // return $this->successResponse('Product deleted successfully.');
        return response()->json(status: 204);

    }
}

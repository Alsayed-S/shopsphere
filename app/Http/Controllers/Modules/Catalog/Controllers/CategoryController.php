<?php

namespace App\Http\Controllers\Modules\Catalog\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\CategoryIndexRequest;
use App\Http\Requests\StoreCategoryRequest;
use App\Http\Requests\UpdateCategoryRequest;
use App\Http\Resources\CategoryResource;
use App\Modules\Catalog\Services\CategoryService;
use App\Traits\ApiResponse;

class CategoryController extends Controller
{
    use ApiResponse;

    public function __construct(
        private CategoryService $categoryService
    ) {}

    /**
     * Display a listing of the resource.
     */
    public function index(CategoryIndexRequest $request)
    {
        $perPage = $request->validated('per_page', 10);

        $categories = $this->categoryService->getAll($perPage);

        return CategoryResource::collection($categories)->additional([
            'success' => true,
            'message' => 'Categories retrieved successfully.',
        ])->response();

    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreCategoryRequest $request)
    {
        $validatedData = $request->validated();

        $category = $this->categoryService->create($validatedData);

        return (new CategoryResource($category))->additional([
            'success' => true,
            'message' => 'Category created successfully.',
        ])->response()->setStatusCode(201);
    }

    /**
     * Display the specified resource.
     */
    public function show(int $id)
    {
        $category = $this->categoryService->getById($id);
        if (! $category) {
            return response()->json([
                'success' => false,
                'message' => 'Category not found.',
            ], 404);
        }

        return (new CategoryResource($category))->additional([
            'success' => true,
            'message' => 'Category retrieved successfully.',
        ])->response();
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateCategoryRequest $request, int $id)
    {

        $validatedData = $request->validated();

        $category = $this->categoryService->getById($id);
        if (! $category) {
            return response()->json([
                'success' => false,
                'message' => 'Category not found.',
            ], 404);
        }

        $updatedCategory = $this->categoryService->update($category, $validatedData);

        return (new CategoryResource($updatedCategory))->additional([
            'success' => true,
            'message' => 'Category updated successfully.',
        ])->response();
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(int $id)
    {
        $category = $this->categoryService->getById($id);
        if (! $category) {
            return response()->json([
                'success' => false,
                'message' => 'Category not found.',
            ], 404);
        }

        $this->categoryService->delete($category);

        return response()->json([
            'success' => true,
            'message' => 'Category deleted successfully.',
        ]);
    }
}

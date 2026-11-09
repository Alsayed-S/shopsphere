<?php

namespace App\Modules\Catalog\Services;

use App\Modules\Catalog\Models\Category;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class CategoryService
{
    public function create(array $data): Category
    {
        return Category::create($data);
    }

    public function update(Category $category, array $data): Category
    {
        $category->update($data);

        return $category->refresh();
    }

    public function delete(Category $category): void
    {
        $category->delete();
    }

    public function getAll(int $perPage = 10): LengthAwarePaginator
    {
        return Category::with(['parent', 'children'])
            ->paginate($perPage);
    }

    public function getById(int $id): ?Category
    {
        return Category::with(['parent', 'children'])->find($id);
    }
}

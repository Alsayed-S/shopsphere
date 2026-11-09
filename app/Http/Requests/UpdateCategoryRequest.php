<?php

namespace App\Http\Requests;

use App\Modules\Catalog\Models\Category;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $category = $this->route('id');

        $categoryId = $category instanceof Category
            ? $category->id
            : $category;

        return [
            'name' => [
                'sometimes',
                'required',
                'string',
                'max:255',
            ],

            'description' => [
                'sometimes',
                'nullable',
                'string',
            ],

            'parent_id' => [
                'sometimes',
                'nullable',
                'exists:categories,id,status,active',
                Rule::notIn([$categoryId]),
            ],

            'slug' => [
                'sometimes',
                'required',
                'string',
                'max:255',
                Rule::unique('categories', 'slug')->ignore($categoryId),
            ],

            'status' => [
                'sometimes',
                'in:active,inactive',
            ],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator): void {

                // Stop if parent_id already has validation errors.
                if ($validator->errors()->has('parent_id')) {
                    return;
                }

                $category = $this->route('id');

                // Resolve the category if the route provides its ID.
                if (! $category instanceof Category) {
                    $category = Category::find($category);
                }

                // Skip validation if category does not exist
                // or parent_id was not provided.
                if (! $category || ! $this->filled('parent_id')) {
                    return;
                }

                $parentId = (int) $this->input('parent_id');

                // Get all descendants of the current category.
                $descendantIds = $this->getDescendantIds($category);

                // Prevent assigning a descendant as the new parent.
                if (in_array($parentId, $descendantIds, true)) {
                    $validator->errors()->add(
                        'parent_id',
                        'A category cannot have one of its descendants as its parent.'
                    );
                }
            },
        ];
    }

    /**
     * Get all descendant category IDs recursively.
     */
    private function getDescendantIds(Category $category): array
    {
        $descendantIds = [];

        foreach ($category->children as $child) {
            $descendantIds[] = $child->id;

            $descendantIds = array_merge(
                $descendantIds,
                $this->getDescendantIds($child)
            );
        }

        return $descendantIds;
    }
}

<?php

namespace Tests\Feature\Catalog;

use App\Modules\Catalog\Models\Category;
use App\Modules\Catalog\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CategoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_can_create_a_root_category(): void
    {
        $category = Category::factory()->create();

        $this->assertDatabaseHas('categories', [
            'id' => $category->id,
            'name' => $category->name,
            'parent_id' => null,
        ]);
    }

    public function test_it_can_create_nested_categories(): void
    {
        // Create the root category.
        $electronics = Category::factory()->create();

        // Create a child category.
        $mobiles = Category::factory()->create([
            'parent_id' => $electronics->id,
        ]);

        // Create a grandchild category.
        $androidPhones = Category::factory()->create([
            'parent_id' => $mobiles->id,
        ]);

        // Verify parent relationships.
        $this->assertTrue($mobiles->parent->is($electronics));
        $this->assertTrue($androidPhones->parent->is($mobiles));

        // Verify children relationships.
        $this->assertTrue(
            $electronics->children->contains('id', $mobiles->id)
        );

        $this->assertTrue(
            $mobiles->children->contains('id', $androidPhones->id)
        );
    }

    public function test_deleting_parent_category_keeps_children_as_root_categories(): void
    {
        // Create parent and child categories.
        $parent = Category::factory()->create();

        $child = Category::factory()->create([
            'parent_id' => $parent->id,
        ]);

        // Delete the parent category.
        $parent->delete();

        // The parent should no longer exist.
        $this->assertDatabaseMissing('categories', [
            'id' => $parent->id,
        ]);

        // The child should still exist.
        $this->assertDatabaseHas('categories', [
            'id' => $child->id,
            'parent_id' => null,
        ]);
    }

    public function test_deleting_category_keeps_products_without_category(): void
    {
        // Create a category.
        $category = Category::factory()->create();

        // Create a product assigned to this category.
        $product = Product::factory()->create([
            'category_id' => $category->id,
        ]);

        // Delete the category.
        $category->delete();

        // The product should still exist without a category.
        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'category_id' => null,
        ]);
    }
}

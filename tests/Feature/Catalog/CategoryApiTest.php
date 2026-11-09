<?php

namespace Tests\Feature\Catalog;

use App\Modules\Catalog\Models\Category;
use App\Modules\Catalog\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CategoryApiTest extends TestCase
{
    use RefreshDatabase;

    /*
    |--------------------------------------------------------------------------
    | Store Tests
    |--------------------------------------------------------------------------
    */

    public function test_it_can_create_a_root_category(): void
    {
        $response = $this->postJson('/api/categories', [
            'name' => 'Electronics',
            'slug' => 'electronics',
            'description' => 'Electronic products',
            'status' => 'active',
        ]);

        $response->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Category created successfully.')
            ->assertJsonPath('data.name', 'Electronics')
            ->assertJsonPath('data.parent_id', null);

        $this->assertDatabaseHas('categories', [
            'name' => 'Electronics',
            'slug' => 'electronics',
            'parent_id' => null,
        ]);
    }

    public function test_it_can_create_a_child_category(): void
    {
        $parent = Category::factory()->create([
            'status' => 'active',
        ]);

        $response = $this->postJson('/api/categories', [
            'name' => 'Mobiles',
            'slug' => 'mobiles',
            'description' => 'Mobile devices',
            'parent_id' => $parent->id,
            'status' => 'active',
        ]);

        $response->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.name', 'Mobiles')
            ->assertJsonPath('data.parent_id', $parent->id);

        $this->assertDatabaseHas('categories', [
            'name' => 'Mobiles',
            'parent_id' => $parent->id,
        ]);
    }

    public function test_it_cannot_create_a_child_category_with_inactive_parent(): void
    {
        $parent = Category::factory()->create([
            'status' => 'inactive',
        ]);

        $response = $this->postJson('/api/categories', [
            'name' => 'Mobiles',
            'slug' => 'mobiles',
            'parent_id' => $parent->id,
            'status' => 'active',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors('parent_id');

        $this->assertDatabaseMissing('categories', [
            'slug' => 'mobiles',
        ]);
    }

    public function test_it_cannot_create_a_category_with_nonexistent_parent(): void
    {
        $response = $this->postJson('/api/categories', [
            'name' => 'Mobiles',
            'slug' => 'mobiles',
            'parent_id' => 999999,
            'status' => 'active',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors('parent_id');

        $this->assertDatabaseMissing('categories', [
            'slug' => 'mobiles',
        ]);
    }

    public function test_it_cannot_create_a_category_with_duplicate_slug(): void
    {
        Category::factory()->create([
            'slug' => 'electronics',
        ]);

        $response = $this->postJson('/api/categories', [
            'name' => 'Electronics 2',
            'slug' => 'electronics',
            'status' => 'active',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors('slug');
    }

    /*
    |--------------------------------------------------------------------------
    | Index Tests
    |--------------------------------------------------------------------------
    */

    public function test_it_can_list_categories_with_pagination(): void
    {
        Category::factory()->count(12)->create();

        $response = $this->getJson('/api/categories?per_page=5');

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath(
                'message',
                'Categories retrieved successfully.'
            )
            ->assertJsonCount(5, 'data')
            ->assertJsonPath('meta.current_page', 1)
            ->assertJsonPath('meta.per_page', 5)
            ->assertJsonPath('meta.total', 12)
            ->assertJsonPath('meta.last_page', 3);
    }

    public function test_it_rejects_invalid_per_page_value(): void
    {
        $response = $this->getJson(
            '/api/categories?per_page=invalid'
        );

        $response->assertUnprocessable()
            ->assertJsonValidationErrors('per_page');
    }

    /*
    |--------------------------------------------------------------------------
    | Show Tests
    |--------------------------------------------------------------------------
    */

    public function test_it_can_show_a_category_with_its_relationships(): void
    {
        $parent = Category::factory()->create();

        $category = Category::factory()->create([
            'parent_id' => $parent->id,
        ]);

        $response = $this->getJson(
            "/api/categories/{$category->id}"
        );

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath(
                'message',
                'Category retrieved successfully.'
            )
            ->assertJsonPath('data.id', $category->id)
            ->assertJsonPath('data.name', $category->name)
            ->assertJsonPath('data.parent.id', $parent->id)
            ->assertJsonPath('data.parent_id', $parent->id);
    }

    public function test_it_returns_404_when_category_does_not_exist(): void
    {
        $response = $this->getJson('/api/categories/999999');

        $response->assertNotFound()
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Category not found.');
    }

    /*
    |--------------------------------------------------------------------------
    | Update Tests
    |--------------------------------------------------------------------------
    */

    public function test_it_can_update_a_category(): void
    {
        $category = Category::factory()->create([
            'name' => 'Laptop',
            'slug' => 'laptop',
        ]);

        $response = $this->putJson(
            "/api/categories/{$category->id}",
            [
                'name' => 'Gaming Laptop',
                'slug' => 'gaming-laptop',
            ]
        );

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath(
                'message',
                'Category updated successfully.'
            )
            ->assertJsonPath('data.name', 'Gaming Laptop')
            ->assertJsonPath('data.slug', 'gaming-laptop');

        $this->assertDatabaseHas('categories', [
            'id' => $category->id,
            'name' => 'Gaming Laptop',
            'slug' => 'gaming-laptop',
        ]);
    }

    public function test_it_returns_404_when_updating_nonexistent_category(): void
    {
        $response = $this->putJson('/api/categories/999999', [
            'name' => 'Updated Category',
        ]);

        $response->assertNotFound()
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Category not found.');
    }

    public function test_it_cannot_update_category_to_be_its_own_parent(): void
    {
        $category = Category::factory()->create([
            'status' => 'active',
        ]);

        $response = $this->putJson(
            "/api/categories/{$category->id}",
            [
                'parent_id' => $category->id,
            ]
        );

        $response->assertUnprocessable()
            ->assertJsonValidationErrors('parent_id');

        $this->assertDatabaseHas('categories', [
            'id' => $category->id,
            'parent_id' => null,
        ]);
    }

    public function test_it_cannot_set_descendant_as_parent(): void
    {
        $electronics = Category::factory()->create([
            'status' => 'active',
        ]);

        $mobiles = Category::factory()->create([
            'parent_id' => $electronics->id,
            'status' => 'active',
        ]);

        $androidPhones = Category::factory()->create([
            'parent_id' => $mobiles->id,
            'status' => 'active',
        ]);

        $response = $this->putJson(
            "/api/categories/{$electronics->id}",
            [
                'parent_id' => $androidPhones->id,
            ]
        );

        $response->assertUnprocessable()
            ->assertJsonValidationErrors('parent_id');

        $this->assertDatabaseHas('categories', [
            'id' => $electronics->id,
            'parent_id' => null,
        ]);
    }

    public function test_it_cannot_update_category_with_duplicate_slug(): void
    {
        Category::factory()->create([
            'slug' => 'electronics',
        ]);

        $category = Category::factory()->create([
            'slug' => 'laptops',
        ]);

        $response = $this->putJson(
            "/api/categories/{$category->id}",
            [
                'slug' => 'electronics',
            ]
        );

        $response->assertUnprocessable()
            ->assertJsonValidationErrors('slug');

        $this->assertDatabaseHas('categories', [
            'id' => $category->id,
            'slug' => 'laptops',
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Destroy Tests
    |--------------------------------------------------------------------------
    */

    public function test_it_can_delete_a_category_and_preserve_related_records(): void
    {
        $category = Category::factory()->create();

        $child = Category::factory()->create([
            'parent_id' => $category->id,
        ]);

        $product = Product::factory()->create([
            'category_id' => $category->id,
        ]);

        $response = $this->deleteJson(
            "/api/categories/{$category->id}"
        );

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath(
                'message',
                'Category deleted successfully.'
            );

        $this->assertDatabaseMissing('categories', [
            'id' => $category->id,
        ]);

        $this->assertDatabaseHas('categories', [
            'id' => $child->id,
            'parent_id' => null,
        ]);

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'category_id' => null,
        ]);
    }

    public function test_it_returns_404_when_deleting_nonexistent_category(): void
    {
        $response = $this->deleteJson('/api/categories/999999');

        $response->assertNotFound()
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Category not found.');
    }
}

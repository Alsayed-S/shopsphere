<?php

namespace Tests\Feature\Catalog;

use App\Modules\Catalog\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test that a product can be created successfully.
     */
    public function test_can_create_a_product(): void
    {
        $productData = [
            'name' => 'Test Product',
            'slug' => 'test-product',
            'description' => 'This is a test product.',
            'price' => 9.99,
            'status' => 'draft',

        ];

        $response = $this->postJson('/api/products', $productData);

        $response->assertStatus(201);

        $response->assertJsonPath('data.name', 'Test Product');
        $response->assertJsonPath('data.slug', 'test-product');
        $response->assertJsonPath('data.price', '9.99');

        $this->assertDatabaseHas('products', [
            'name' => 'Test Product',
            'slug' => 'test-product',
        ]);
    }

    /**
     * Test that duplicate product slugs are rejected.
     */
    public function test_cannot_create_product_with_duplicate_slug(): void
    {
        $productData = [
            'name' => 'First Product',
            'slug' => 'duplicate-product',
            'description' => 'First product description.',
            'price' => 10.00,
            'status' => 'draft',
        ];

        // Create the first product.
        $firstResponse = $this->postJson('/api/products', $productData);

        // Ensure the first product was created successfully.
        $firstResponse->assertStatus(201);

        // Attempt to create another product with the same slug.
        $duplicateResponse = $this->postJson('/api/products', [
            'name' => 'Second Product',
            'slug' => 'duplicate-product',
            'description' => 'Second product description.',
            'price' => 20.00,
            'status' => 'draft',
        ]);

        $duplicateResponse->assertStatus(422);

        // Ensure only the original product exists.
        $this->assertDatabaseCount('products', 1);
    }

    public function test_can_show_a_product(): void
{
    // Arrange
    $product = Product::create([
        'name' => 'Test Product',
        'slug' => 'test-product',
        'description' => 'Test description',
        'price' => 10,
        'status' => 'draft',
    ]);

    // Act
    $response = $this->getJson("/api/products/{$product->id}");

    // Assert
    $response->assertStatus(200);

    $response->assertJsonPath('data.id', $product->id);
    $response->assertJsonPath('data.name', 'Test Product');
}


public function test_returns_404_when_product_not_found(): void
{
    $response = $this->getJson('/api/products/99999');

    $response->assertStatus(404);
}


/**
 * Test that a product can be updated successfully.
 */
public function test_can_update_a_product(): void
{
    $product = Product::create([
        'name' => 'Old Product',
        'slug' => 'old-product',
        'description' => 'Old description',
        'price' => 10,
        'status' => 'draft',
    ]);

    $response = $this->putJson("/api/products/{$product->id}", [
        'name' => 'Updated Product',
        'slug' => 'updated-product',
        'description' => 'Updated description',
        'price' => '20',
        'status' => 'published',
    ]);

    $response->assertStatus(200);

    $response->assertJsonPath('data.name', 'Updated Product');
    $response->assertJsonPath('data.slug', 'updated-product');
    $response->assertJsonPath('data.price', '20.00');
    $response->assertJsonPath('data.status', 'published');
    $this->assertDatabaseHas('products', [
        'id' => $product->id,
        'name' => 'Updated Product',
        'slug' => 'updated-product',
    ]);
}

/**
 * Test that a product can keep its current slug.
 */
public function test_can_update_product_without_changing_its_slug(): void
{
    $product = Product::create([
        'name' => 'Old Product',
        'slug' => 'old-product',
        'description' => 'Old description',
        'price' => 10,
        'status' => 'draft',
    ]);

    $response = $this->putJson("/api/products/{$product->id}", [
        'name' => 'Updated Product',
        'slug' => 'old-product',
        'description' => 'Updated description',
        'price' => 20,
        'status' => 'published',
    ]);

    $response->assertStatus(200);

    $this->assertDatabaseHas('products', [
        'id' => $product->id,
        'slug' => 'old-product',
        'name' => 'Updated Product',
    ]);
}

/**
 * Test that a product cannot use another product's slug.
 */
public function test_cannot_update_product_with_another_products_slug(): void
{
    $firstProduct = Product::create([
        'name' => 'First Product',
        'slug' => 'first-product',
        'description' => 'First description',
        'price' => 10,
        'status' => 'draft',
    ]);

    $secondProduct = Product::create([
        'name' => 'Second Product',
        'slug' => 'second-product',
        'description' => 'Second description',
        'price' => 20,
        'status' => 'draft',
    ]);

    $response = $this->putJson("/api/products/{$secondProduct->id}", [
        'name' => 'Updated Second Product',
        'slug' => 'first-product',
        'description' => 'Updated description',
        'price' => 30,
        'status' => 'published',
    ]);

    $response->assertStatus(422);

    $this->assertDatabaseHas('products', [
        'id' => $secondProduct->id,
        'slug' => 'second-product',
    ]);
}

/**
 * Test that invalid product data is rejected.
 */
public function test_cannot_update_product_with_invalid_data(): void
{
    $product = Product::create([
        'name' => 'Test Product',
        'slug' => 'test-product',
        'description' => 'Test description',
        'price' => 10,
        'status' => 'draft',
    ]);

    $response = $this->putJson("/api/products/{$product->id}", [
        'name' => '',
        'slug' => 'test-product',
        'description' => 'Updated description',
        'price' => -5,
        'status' => 'invalid',
    ]);

    $response->assertStatus(422);

    $response->assertJsonValidationErrors([
        'name',
        'price',
        'status',
    ]);
}

/**
 * Test that updating a non-existing product returns 404.
 */
public function test_returns_404_when_updating_non_existing_product(): void
{
    $response = $this->putJson('/api/products/99999', [
        'name' => 'Updated Product',
        'slug' => 'updated-product',
        'description' => 'Updated description',
        'price' => 20,
        'status' => 'published',
    ]);

    $response->assertStatus(404);
}


public function test_can_delete_a_product(): void
{
    $product = Product::create([
        'name' => 'Test Product',
        'slug' => 'test-product',
        'description' => 'Test description',
        'price' => 10,
        'status' => 'draft',
    ]);

    $response = $this->deleteJson("/api/products/{$product->id}");

    $response->assertStatus(204);
    $response->assertNoContent();

    $this->assertDatabaseMissing('products', [
        'id' => $product->id,
    ]);
}

public function test_returns_404_when_deleting_non_existing_product(): void
{
    $response = $this->deleteJson('/api/products/99999');

    $response->assertStatus(404);
}

// pagination test
public function test_can_get_paginated_products(): void
{
    // Arrange
    Product::factory()->count(30)->create();

    // Act
    $response = $this->getJson('/api/products?per_page=10');

    // Assert
    $response->assertOk();

    $response->assertJsonCount(10, 'data');

    $response->assertJsonPath('meta.current_page', 1);
    $response->assertJsonPath('meta.per_page', 10);
    $response->assertJsonPath('meta.total', 30);
    $response->assertJsonPath('meta.last_page', 3);
}
public function test_per_page_must_be_between_1_and_100(): void
{
    $response = $this->getJson('/api/products?per_page=150');

    $response->assertUnprocessable();
    $response->assertJsonValidationErrors('per_page');
}
public function test_per_page_cannot_be_zero(): void
{
    $response = $this->getJson('/api/products?per_page=0');

    $response->assertUnprocessable();
    $response->assertJsonValidationErrors('per_page');
}
public function test_per_page_must_be_an_integer(): void
{
    $response = $this->getJson('/api/products?per_page=abc');

    $response->assertUnprocessable();
    $response->assertJsonValidationErrors('per_page');
}

public function test_default_per_page_is_ten(): void
{
    Product::factory()->count(12)->create();

    $response = $this->getJson('/api/products');

    $response->assertOk();
    $response->assertJsonCount(10, 'data');
    $response->assertJsonPath('meta.per_page', 10);
    $response->assertJsonPath('meta.total', 12);
}

public function test_can_get_second_page_of_products(): void
{
    Product::factory()->count(25)->create();

    $response = $this->getJson('/api/products?page=2&per_page=10');

    $response->assertOk();
    $response->assertJsonCount(10, 'data');
    $response->assertJsonPath('meta.current_page', 2);
    $response->assertJsonPath('meta.last_page', 3);
}

}
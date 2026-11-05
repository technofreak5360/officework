<?php

namespace Tests\Feature\Api;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class ProductApiTest extends TestCase
{
    public function test_featured_returns_the_chosen_products_in_the_chosen_order(): void
    {
        config(['catalogue.featured_product_ids' => [18, 3, 12, 5, 11]]);

        $this->getJson('/api/products/featured')
            ->assertOk()
            ->assertJsonCount(5, 'data')
            ->assertJsonPath('data.*.id', [18, 3, 12, 5, 11]);
    }

    public function test_featured_skips_and_logs_an_id_that_no_longer_exists(): void
    {
        config(['catalogue.featured_product_ids' => [3, 999]]);
        Log::shouldReceive('warning')->once();

        $this->getJson('/api/products/featured')
            ->assertOk()
            ->assertJsonPath('data.*.id', [3]);
    }

    public function test_product_details_use_our_own_format(): void
    {
        $this->getJson('/api/products/3')
            ->assertOk()
            ->assertJsonPath('data.title', 'Mens Cotton Jacket')
            ->assertJsonPath('data.category', ['name' => "men's clothing", 'slug' => 'mens-clothing'])
            ->assertJsonPath('data.rating', ['rate' => 4.7, 'count' => 500])
            ->assertJsonStructure(['data' => ['id', 'title', 'price', 'description', 'category', 'imageUrl', 'rating']])
            ->assertJsonMissingPath('data.image');
    }

    public function test_an_unknown_product_returns_404(): void
    {
        $this->getJson('/api/products/999')
            ->assertNotFound()
            ->assertJsonPath('message', 'Product not found.');
    }

    public function test_a_non_numeric_product_id_returns_404(): void
    {
        $this->getJson('/api/products/abc')->assertNotFound();
    }

    public function test_search_only_returns_matching_products(): void
    {
        $response = $this->getJson('/api/products?search=SSD')->assertOk();

        $this->assertNotEmpty($response->json('data'));
        foreach ($response->json('data') as $product) {
            $this->assertStringContainsStringIgnoringCase(
                'ssd',
                $product['title'].' '.$product['description'].' '.$product['category']['name'],
            );
        }
    }

    public function test_search_with_no_match_returns_an_empty_list(): void
    {
        $this->getJson('/api/products?search=zzz-no-such-product')
            ->assertOk()
            ->assertExactJson(['data' => []]);
    }

    public function test_a_blank_search_returns_all_products(): void
    {
        $this->getJson('/api/products?search=%20%20')
            ->assertOk()
            ->assertJsonCount(20, 'data');
    }

    public function test_an_invalid_search_returns_422(): void
    {
        $this->getJson('/api/products?search='.str_repeat('a', 101))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('search');

        $this->getJson('/api/products?search[]=a')
            ->assertUnprocessable()
            ->assertJsonValidationErrors('search');
    }

    public function test_an_upstream_failure_returns_a_clean_503(): void
    {
        config(['catalogue.driver' => 'fakestore']);
        Http::fake(['*' => Http::response('', 500)]);

        $response = $this->getJson('/api/products/3');

        $response->assertServiceUnavailable()->assertJsonStructure(['message']);
        $this->assertStringNotContainsString('Fake Store', $response->getContent());
    }
}

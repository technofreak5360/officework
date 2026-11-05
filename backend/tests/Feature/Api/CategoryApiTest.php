<?php

namespace Tests\Feature\Api;

use Tests\TestCase;

class CategoryApiTest extends TestCase
{
    public function test_it_lists_categories_with_slugs(): void
    {
        $this->getJson('/api/categories')
            ->assertOk()
            ->assertJsonCount(4, 'data')
            ->assertJsonFragment(['name' => "men's clothing", 'slug' => 'mens-clothing']);
    }

    public function test_category_page_splits_top_rated_from_other_products(): void
    {
        $this->getJson('/api/categories/jewelery/products')
            ->assertOk()
            ->assertJsonPath('data.category.slug', 'jewelery')
            ->assertJsonPath('data.topRated.*.id', [5, 6, 7])
            ->assertJsonPath('data.others.*.id', [8]);
    }

    public function test_rating_ties_break_on_review_count(): void
    {
        // Products 11 and 12 both rate 4.8, and 12 has more reviews.
        $this->getJson('/api/categories/electronics/products')
            ->assertOk()
            ->assertJsonPath('data.topRated.*.id', [12, 11, 9]);
    }

    public function test_every_product_in_a_category_appears_exactly_once(): void
    {
        $data = $this->getJson('/api/categories/electronics/products')->json('data');

        $ids = array_column([...$data['topRated'], ...$data['others']], 'id');
        sort($ids);

        $this->assertSame([9, 10, 11, 12, 13, 14], $ids);
    }

    public function test_top_rated_count_is_configurable(): void
    {
        config(['catalogue.category_top_rated_count' => 1]);

        $this->getJson('/api/categories/jewelery/products')
            ->assertJsonPath('data.topRated.*.id', [5])
            ->assertJsonCount(3, 'data.others');
    }

    public function test_an_unknown_category_returns_404(): void
    {
        $this->getJson('/api/categories/nope/products')
            ->assertNotFound()
            ->assertJsonPath('message', 'Category not found.');
    }
}

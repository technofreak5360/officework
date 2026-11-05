<?php

namespace Tests\Feature\Catalogue;

use App\Catalogue\FakeStoreCatalogue;
use App\Catalogue\FixtureCatalogue;
use App\Catalogue\Product;
use App\Catalogue\ProductCatalogue;
use Closure;
use Illuminate\Http\Client\Factory;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Every product source must pass these tests. To add a source, add it to the
 * provider below. If it passes, the rest of the app works with it unchanged.
 */
class ProductCatalogueContractTest extends TestCase
{
    /** @return array<string, array{Closure(): ProductCatalogue}> */
    public static function catalogues(): array
    {
        return [
            'fakestore' => [function (): ProductCatalogue {
                Http::fake(['*' => Http::response(
                    json_decode(file_get_contents(base_path('tests/Fixtures/fakestore-products.json')), true),
                )]);

                return new FakeStoreCatalogue(app(Factory::class), cache()->store(), 'https://fakestore.test', 5, 600);
            }],
            'fixture' => [fn (): ProductCatalogue => new FixtureCatalogue(resource_path('fixtures/products.json'))],
        ];
    }

    #[DataProvider('catalogues')]
    public function test_all_returns_every_product(Closure $make): void
    {
        $this->assertCount(20, $make()->all());
    }

    #[DataProvider('catalogues')]
    public function test_find_maps_every_field(Closure $make): void
    {
        $product = $make()->find(3);

        $this->assertInstanceOf(Product::class, $product);
        $this->assertSame(3, $product->id);
        $this->assertSame('Mens Cotton Jacket', $product->title);
        $this->assertSame(55.99, $product->price);
        $this->assertNotSame('', $product->description);
        $this->assertSame("men's clothing", $product->category->name);
        $this->assertSame('mens-clothing', $product->category->slug);
        $this->assertStringStartsWith('https://', $product->imageUrl);
        $this->assertSame(4.7, $product->ratingRate);
        $this->assertSame(500, $product->ratingCount);
    }

    #[DataProvider('catalogues')]
    public function test_find_returns_null_for_an_unknown_id(Closure $make): void
    {
        $this->assertNull($make()->find(999));
    }

    #[DataProvider('catalogues')]
    public function test_categories_are_unique_with_url_safe_slugs(Closure $make): void
    {
        $slugs = array_map(fn ($category) => $category->slug, $make()->categories());

        $this->assertEqualsCanonicalizing(
            ['electronics', 'jewelery', 'mens-clothing', 'womens-clothing'],
            $slugs,
        );
    }

    #[DataProvider('catalogues')]
    public function test_in_category_only_returns_that_category(Closure $make): void
    {
        $products = $make()->inCategory('jewelery');

        $this->assertCount(4, $products);
        foreach ($products as $product) {
            $this->assertSame('jewelery', $product->category->slug);
        }
    }

    #[DataProvider('catalogues')]
    public function test_in_category_is_empty_for_an_unknown_slug(Closure $make): void
    {
        $this->assertSame([], $make()->inCategory('nope'));
    }

    #[DataProvider('catalogues')]
    public function test_search_is_case_insensitive_and_covers_title_description_and_category(Closure $make): void
    {
        $catalogue = $make();
        $ids = fn (string $term) => array_map(fn (Product $p) => $p->id, $catalogue->search($term));

        $this->assertContains(3, $ids('COTTON JACKET'), 'title');
        $this->assertContains(1, $ids('walks in the forest'), 'description');
        $this->assertContains(9, $ids('ELECTRONICS'), 'category');
    }

    #[DataProvider('catalogues')]
    public function test_search_returns_nothing_when_no_text_matches(Closure $make): void
    {
        $this->assertSame([], $make()->search('zzz-no-such-product'));
    }

    #[DataProvider('catalogues')]
    public function test_search_treats_regex_characters_as_plain_text(Closure $make): void
    {
        $this->assertSame([], $make()->search('.*'));
    }
}

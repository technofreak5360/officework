<?php

namespace Tests\Feature\Catalogue;

use App\Catalogue\CatalogueUnavailable;
use App\Catalogue\ProductCatalogue;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class FakeStoreCatalogueTest extends TestCase
{
    private const VALID_ROW = [
        'id' => 1,
        'title' => 'Jacket',
        'price' => 10.5,
        'description' => 'Warm',
        'category' => "men's clothing",
        'image' => 'https://fakestore.test/1.png',
        'rating' => ['rate' => 4.5, 'count' => 10],
    ];

    protected function setUp(): void
    {
        parent::setUp();

        config(['catalogue.driver' => 'fakestore']);
    }

    private function catalogue(): ProductCatalogue
    {
        return app(ProductCatalogue::class);
    }

    public function test_repeat_reads_use_the_cache(): void
    {
        Http::fake(['*' => Http::response([self::VALID_ROW])]);

        $this->catalogue()->all();
        $this->catalogue()->find(1);
        $this->catalogue()->search('jacket');

        Http::assertSentCount(1);
    }

    public function test_a_failure_is_not_cached(): void
    {
        Http::fake(['*' => Http::sequence()
            ->push('', 500)
            ->push([self::VALID_ROW])]);

        try {
            $this->catalogue()->all();
            $this->fail('Expected the first read to fail.');
        } catch (CatalogueUnavailable) {
            // Expected.
        }

        $this->assertCount(1, $this->catalogue()->all());
    }

    public function test_a_connection_failure_is_reported_as_unavailable(): void
    {
        Http::fake(['*' => Http::failedConnection()]);

        $this->expectException(CatalogueUnavailable::class);

        $this->catalogue()->all();
    }

    /** @return array<string, array{mixed, int}> */
    public static function unusableResponses(): array
    {
        return [
            'server error' => ['', 500],
            'blocked' => ['Forbidden', 403],
            'not JSON' => ['<html></html>', 200],
            'empty body' => ['', 200],
            'empty list' => [[], 200],
            'object, not a list' => [['id' => 1], 200],
            'row missing a field' => [[['id' => 1, 'title' => 'No price']], 200],
            'row with a wrong type' => [[[...self::VALID_ROW, 'id' => 'one']], 200],
        ];
    }

    #[DataProvider('unusableResponses')]
    public function test_an_unusable_response_is_reported_as_unavailable(mixed $body, int $status): void
    {
        Http::fake(['*' => Http::response($body, $status)]);

        $this->expectException(CatalogueUnavailable::class);

        $this->catalogue()->all();
    }
}

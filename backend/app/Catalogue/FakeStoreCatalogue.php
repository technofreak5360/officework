<?php

namespace App\Catalogue;

use Illuminate\Contracts\Cache\Repository as Cache;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Factory as Http;

/**
 * Reads products from the Fake Store API.
 *
 * It always fetches the full list and looks products up in it. Fake Store answers
 * an unknown ID with 200 and an empty body, so GET /products/{id} can't be trusted
 * to report a missing product.
 */
class FakeStoreCatalogue extends InMemoryCatalogue
{
    private const CACHE_KEY = 'catalogue.fakestore.products';

    public function __construct(
        private readonly Http $http,
        private readonly Cache $cache,
        private readonly string $baseUrl,
        private readonly int $timeoutSeconds,
        private readonly int $cacheSeconds,
    ) {}

    public function all(): array
    {
        // Raw arrays are cached rather than Product objects, so a change to
        // Product can't break unserializing entries that are already cached.
        // A failed fetch throws, so failures are never cached.
        $rows = $this->cache->remember(self::CACHE_KEY, $this->cacheSeconds, fn () => $this->fetch());

        return array_map($this->toProduct(...), $rows);
    }

    /** @return list<array<string, mixed>> */
    private function fetch(): array
    {
        try {
            $response = $this->http
                ->baseUrl($this->baseUrl)
                ->acceptJson()
                ->timeout($this->timeoutSeconds)
                ->get('/products');
        } catch (ConnectionException $e) {
            throw new CatalogueUnavailable('Could not reach Fake Store.', previous: $e);
        }

        if (! $response->successful()) {
            throw new CatalogueUnavailable("Fake Store responded with HTTP {$response->status()}.");
        }

        $rows = $response->json();

        if (! is_array($rows) || $rows === [] || ! array_is_list($rows)) {
            throw new CatalogueUnavailable('Fake Store returned an unexpected product list.');
        }

        foreach ($rows as $row) {
            if (! $this->isValidRow($row)) {
                throw new CatalogueUnavailable('Fake Store returned a malformed product.');
            }
        }

        return $rows;
    }

    private function isValidRow(mixed $row): bool
    {
        return is_array($row)
            && is_int($row['id'] ?? null)
            && is_string($row['title'] ?? null)
            && is_numeric($row['price'] ?? null)
            && is_string($row['description'] ?? null)
            && is_string($row['category'] ?? null)
            && is_string($row['image'] ?? null)
            && is_numeric($row['rating']['rate'] ?? null)
            && is_numeric($row['rating']['count'] ?? null);
    }

    /** @param array<string, mixed> $row */
    private function toProduct(array $row): Product
    {
        return new Product(
            id: $row['id'],
            title: $row['title'],
            price: (float) $row['price'],
            description: $row['description'],
            category: Category::fromName($row['category']),
            imageUrl: $row['image'],
            ratingRate: (float) $row['rating']['rate'],
            ratingCount: (int) $row['rating']['count'],
        );
    }
}

<?php

namespace App\Catalogue;

/**
 * Reads products from a local JSON file. Set CATALOGUE_DRIVER=fixture to run the
 * app without Fake Store, for example during an outage.
 */
class FixtureCatalogue extends InMemoryCatalogue
{
    /** @var list<Product>|null */
    private ?array $products = null;

    public function __construct(
        private readonly string $path,
    ) {}

    public function all(): array
    {
        return $this->products ??= $this->load();
    }

    /** @return list<Product> */
    private function load(): array
    {
        $contents = @file_get_contents($this->path);
        $rows = $contents === false ? null : json_decode($contents, true);

        if (! is_array($rows)) {
            throw new CatalogueUnavailable("Could not read the product fixture at {$this->path}.");
        }

        return array_map(fn (array $row) => new Product(
            id: $row['id'],
            title: $row['title'],
            price: (float) $row['price'],
            description: $row['description'],
            category: Category::fromName($row['category']),
            imageUrl: $row['imageUrl'],
            ratingRate: (float) $row['rating']['rate'],
            ratingCount: (int) $row['rating']['count'],
        ), $rows);
    }
}

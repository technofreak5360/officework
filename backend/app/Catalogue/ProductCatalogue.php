<?php

namespace App\Catalogue;

/**
 * The boundary between the app and wherever product data comes from.
 *
 * Filtering is part of the contract, not done by callers, so a future source
 * can use its own search or database queries instead of loading every product.
 *
 * @throws CatalogueUnavailable from any method when the source can't be read
 */
interface ProductCatalogue
{
    /** @return list<Product> */
    public function all(): array;

    public function find(int $id): ?Product;

    /** @return list<Category> */
    public function categories(): array;

    /** @return list<Product> */
    public function inCategory(string $slug): array;

    /**
     * Case-insensitive match of the whole term against title, description and
     * category name.
     *
     * @return list<Product>
     */
    public function search(string $term): array;
}

<?php

namespace App\Catalogue;

/**
 * Query logic for sources that load the full product list. That suits a catalogue
 * of about 20 products. A larger source should implement ProductCatalogue directly.
 */
abstract class InMemoryCatalogue implements ProductCatalogue
{
    /** @return list<Product> */
    abstract public function all(): array;

    public function find(int $id): ?Product
    {
        foreach ($this->all() as $product) {
            if ($product->id === $id) {
                return $product;
            }
        }

        return null;
    }

    public function categories(): array
    {
        $categories = [];

        foreach ($this->all() as $product) {
            $categories[$product->category->slug] = $product->category;
        }

        return array_values($categories);
    }

    public function inCategory(string $slug): array
    {
        return array_values(array_filter(
            $this->all(),
            fn (Product $product) => $product->category->slug === $slug,
        ));
    }

    public function search(string $term): array
    {
        return array_values(array_filter(
            $this->all(),
            fn (Product $product) => mb_stripos($product->title, $term) !== false
                || mb_stripos($product->description, $term) !== false
                || mb_stripos($product->category->name, $term) !== false,
        ));
    }
}

<?php

namespace App\Catalogue;

/**
 * The app's own view of a product. Data sources map to this, so changing source
 * doesn't affect the API or the frontend.
 */
final readonly class Product
{
    public function __construct(
        public int $id,
        public string $title,
        public float $price,
        public string $description,
        public Category $category,
        public string $imageUrl,
        public float $ratingRate,
        public int $ratingCount,
    ) {}
}

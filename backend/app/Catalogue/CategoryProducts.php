<?php

namespace App\Catalogue;

/**
 * Splits a category into its top rated products and the rest.
 *
 * Unlike the hand-picked home page products, "top rated" here comes from the
 * rating score, because there are no per-category picks to read.
 */
class CategoryProducts
{
    public function __construct(
        private readonly ProductCatalogue $catalogue,
        private readonly int $topRatedCount,
    ) {}

    /** Returns null when no products belong to the slug. */
    public function forSlug(string $slug): ?CategoryListing
    {
        $products = $this->catalogue->inCategory($slug);

        if ($products === []) {
            return null;
        }

        // Ties break on review count, then ID, so the order is always the same.
        $byRating = $products;
        usort($byRating, fn (Product $a, Product $b) => [$b->ratingRate, $b->ratingCount, $a->id]
            <=> [$a->ratingRate, $a->ratingCount, $b->id]);

        $topRated = array_slice($byRating, 0, $this->topRatedCount);
        $topRatedIds = array_map(fn (Product $product) => $product->id, $topRated);

        return new CategoryListing(
            category: $products[0]->category,
            topRated: $topRated,
            others: array_values(array_filter(
                $products,
                fn (Product $product) => ! in_array($product->id, $topRatedIds, true),
            )),
        );
    }
}

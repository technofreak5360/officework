<?php

namespace App\Catalogue;

use Illuminate\Support\Facades\Log;

/**
 * The products the owner has chosen to feature on the home page.
 *
 * The choice is a list of IDs in config for now. This is the one place to change
 * if it moves to a database or an admin screen.
 */
class FeaturedProducts
{
    /** @param list<int> $ids */
    public function __construct(
        private readonly ProductCatalogue $catalogue,
        private readonly array $ids,
    ) {}

    /**
     * Keeps the configured order. An ID that no longer exists is skipped and logged,
     * so one stale ID doesn't take down the home page.
     *
     * @return list<Product>
     */
    public function all(): array
    {
        $products = [];

        foreach ($this->ids as $id) {
            $product = $this->catalogue->find($id);

            if ($product === null) {
                Log::warning('Featured product not found in the catalogue.', ['id' => $id]);

                continue;
            }

            $products[] = $product;
        }

        return $products;
    }
}

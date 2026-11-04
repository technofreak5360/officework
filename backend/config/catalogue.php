<?php

return [

    // Where product data comes from: "fakestore" or "fixture".
    'driver' => env('CATALOGUE_DRIVER', 'fakestore'),

    'fakestore' => [
        'base_url' => env('FAKESTORE_BASE_URL', 'https://fakestoreapi.com'),
        'timeout_seconds' => (int) env('FAKESTORE_TIMEOUT_SECONDS', 5),
        'cache_seconds' => (int) env('FAKESTORE_CACHE_SECONDS', 600),
    ],

    'fixture' => [
        'path' => resource_path('fixtures/products.json'),
    ],

    // The owner's home page picks, in display order.
    'featured_product_ids' => array_map('intval', explode(',', env('FEATURED_PRODUCT_IDS', '3,5,11,12,18'))),

    // How many products a category page shows as top rated.
    'category_top_rated_count' => (int) env('CATEGORY_TOP_RATED_COUNT', 3),

];

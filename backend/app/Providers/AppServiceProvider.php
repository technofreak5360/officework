<?php

namespace App\Providers;

use App\Catalogue\CategoryProducts;
use App\Catalogue\FakeStoreCatalogue;
use App\Catalogue\FeaturedProducts;
use App\Catalogue\FixtureCatalogue;
use App\Catalogue\ProductCatalogue;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Http\Client\Factory as Http;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use InvalidArgumentException;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Adding a product source means a new class and a new case here.
        $this->app->singleton(ProductCatalogue::class, fn (Application $app) => match ($driver = config('catalogue.driver')) {
            'fakestore' => new FakeStoreCatalogue(
                $app->make(Http::class),
                $app->make('cache.store'),
                config('catalogue.fakestore.base_url'),
                config('catalogue.fakestore.timeout_seconds'),
                config('catalogue.fakestore.cache_seconds'),
            ),
            'fixture' => new FixtureCatalogue(config('catalogue.fixture.path')),
            default => throw new InvalidArgumentException("Unknown catalogue driver [{$driver}]."),
        });

        $this->app->bind(FeaturedProducts::class, fn (Application $app) => new FeaturedProducts(
            $app->make(ProductCatalogue::class),
            config('catalogue.featured_product_ids'),
        ));

        $this->app->bind(CategoryProducts::class, fn (Application $app) => new CategoryProducts(
            $app->make(ProductCatalogue::class),
            config('catalogue.category_top_rated_count'),
        ));
    }

    public function boot(): void
    {
        RateLimiter::for('enquiries', fn (Request $request) => Limit::perMinute(5)->by($request->ip()));
    }
}

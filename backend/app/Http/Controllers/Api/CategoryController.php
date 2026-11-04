<?php

namespace App\Http\Controllers\Api;

use App\Catalogue\CategoryProducts;
use App\Catalogue\ProductCatalogue;
use App\Http\Controllers\Controller;
use App\Http\Resources\CategoryListingResource;
use App\Http\Resources\CategoryResource;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class CategoryController extends Controller
{
    public function index(ProductCatalogue $catalogue): AnonymousResourceCollection
    {
        return CategoryResource::collection($catalogue->categories());
    }

    public function products(string $slug, CategoryProducts $categoryProducts): CategoryListingResource
    {
        $listing = $categoryProducts->forSlug($slug);

        abort_if($listing === null, 404, 'Category not found.');

        return new CategoryListingResource($listing);
    }
}

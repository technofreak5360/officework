<?php

namespace App\Http\Controllers\Api;

use App\Catalogue\FeaturedProducts;
use App\Catalogue\ProductCatalogue;
use App\Http\Controllers\Controller;
use App\Http\Resources\ProductResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ProductController extends Controller
{
    public function index(Request $request, ProductCatalogue $catalogue): AnonymousResourceCollection
    {
        $validated = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
        ]);

        $term = $validated['search'] ?? null;

        return ProductResource::collection(
            $term === null ? $catalogue->all() : $catalogue->search($term)
        );
    }

    public function featured(FeaturedProducts $featured): AnonymousResourceCollection
    {
        return ProductResource::collection($featured->all());
    }

    public function show(int $id, ProductCatalogue $catalogue): ProductResource
    {
        $product = $catalogue->find($id);

        abort_if($product === null, 404, 'Product not found.');

        return new ProductResource($product);
    }
}

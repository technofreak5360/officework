<?php

namespace App\Http\Resources;

use App\Catalogue\Product;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @property-read Product $resource */
class ProductResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource->id,
            'title' => $this->resource->title,
            'price' => $this->resource->price,
            'description' => $this->resource->description,
            'category' => new CategoryResource($this->resource->category),
            'imageUrl' => $this->resource->imageUrl,
            'rating' => [
                'rate' => $this->resource->ratingRate,
                'count' => $this->resource->ratingCount,
            ],
        ];
    }
}

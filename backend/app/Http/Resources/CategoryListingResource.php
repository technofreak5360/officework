<?php

namespace App\Http\Resources;

use App\Catalogue\CategoryListing;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @property-read CategoryListing $resource */
class CategoryListingResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'category' => new CategoryResource($this->resource->category),
            'topRated' => ProductResource::collection($this->resource->topRated),
            'others' => ProductResource::collection($this->resource->others),
        ];
    }
}

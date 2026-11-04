<?php

namespace App\Http\Controllers\Api;

use App\Catalogue\ProductCatalogue;
use App\Enquiries\SubmitEnquiry;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreEnquiryRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;

class EnquiryController extends Controller
{
    public function store(
        StoreEnquiryRequest $request,
        ProductCatalogue $catalogue,
        SubmitEnquiry $submitEnquiry,
    ): JsonResponse {
        $product = $catalogue->find($request->integer('productId'));

        if ($product === null) {
            throw ValidationException::withMessages([
                'productId' => 'The selected product does not exist.',
            ]);
        }

        $submitEnquiry->handle($product, $request->safe()->only(['name', 'email', 'phone', 'message']));

        return response()->json(['message' => 'Thank you for your enquiry.'], 201);
    }
}

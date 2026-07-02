<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Store;

use App\Domain\Store\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;

class ProductController extends Controller
{
    public function index(): JsonResponse
    {
        $products = Product::with('artist.artistProfile')
            ->where('is_published', true)
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return response()->json($products);
    }

    public function show(Product $product): JsonResponse
    {
        if (! $product->is_published) {
            return response()->json(['message' => 'Not found'], 404);
        }

        $product->load('artist.artistProfile');

        return response()->json(['data' => $product]);
    }
}

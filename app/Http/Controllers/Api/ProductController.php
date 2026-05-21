<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Product\StoreProductRequest;
use App\Http\Requests\Product\UpdateProductRequest;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

class ProductController extends Controller
{
    private const CACHE_TTL = 300; // 5 minutes

    /**
     * GET /api/products
     */
    public function index(Request $request): JsonResponse
    {
        $search   = $request->query('search');
        $category = $request->query('category');
        $limit    = (int) $request->query('limit', 10);
        $page     = (int) $request->query('page', 1);

        $cacheKey = 'products:' . md5(serialize($request->query()));

        $result = Cache::remember($cacheKey, self::CACHE_TTL, function () use ($search, $category, $limit) {
            $query = Product::query();

            if ($search) {
                $query->where('title', 'like', "%{$search}%");
            }

            if ($category) {
                $query->where('category', $category);
            }

            return $query->latest()->paginate($limit);
        });

        return response()->json([
            'success' => true,
            'data'    => $result->items(),
            'meta'    => [
                'total'        => $result->total(),
                'per_page'     => $result->perPage(),
                'current_page' => $result->currentPage(),
                'last_page'    => $result->lastPage(),
            ],
        ]);
    }

    /**
     * GET /api/products/{id}
     */
    public function show(int $id): JsonResponse
    {
        $cacheKey = "product:{$id}";

        $product = Cache::remember($cacheKey, self::CACHE_TTL, function () use ($id) {
            return Product::find($id);
        });

        if (! $product) {
            return response()->json([
                'success' => false,
                'message' => "Product with ID {$id} not found",
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data'    => $product,
        ]);
    }

    /**
     * POST /api/products
     */
    public function store(StoreProductRequest $request): JsonResponse
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        $product = Product::create([
            ...$request->validated(),
            'created_by'    => $user->name,
            'created_by_id' => $user->id,
            'updated_by'    => $user->name,
            'updated_by_id' => $user->id,
        ]);

        Cache::flush(); // invalidate product list caches

        return response()->json([
            'success' => true,
            'message' => 'Product created successfully',
            'data'    => $product,
        ], 201);
    }

    /**
     * PUT /api/products/{id}
     */
    public function update(UpdateProductRequest $request, int $id): JsonResponse
    {
        $product = Product::find($id);

        if (! $product) {
            return response()->json([
                'success' => false,
                'message' => "Product with ID {$id} not found",
            ], 404);
        }

        /** @var \App\Models\User $user */
        $user = Auth::user();

        $product->update([
            ...$request->validated(),
            'updated_by'    => $user->name,
            'updated_by_id' => $user->id,
        ]);

        Cache::forget("product:{$id}");
        Cache::flush();

        return response()->json([
            'success' => true,
            'message' => 'Product updated successfully',
            'data'    => $product->fresh(),
        ]);
    }

    /**
     * DELETE /api/products/{id}
     */
    public function destroy(int $id): JsonResponse
    {
        $product = Product::find($id);

        if (! $product) {
            return response()->json([
                'success' => false,
                'message' => "Product with ID {$id} not found",
            ], 404);
        }

        $product->delete();

        Cache::forget("product:{$id}");
        Cache::flush();

        return response()->json([
            'success' => true,
            'message' => 'Product deleted successfully',
        ]);
    }
}

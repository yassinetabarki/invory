<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Resources\OrderStockProductResource;
use App\Models\Order;
use App\Services\Orders\OrderProductSearch;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

/**
 * "Search products" on New order and Edit order (INV-13, AC-1; ADR-0009 rule 5, ADR-0025): `GET orders/products/search?q=` answers with
 * at most 6 active products and their shelf stock per colour and size, and how many products the search found. Read-only.
 */
final class OrderProductSearchController extends Controller
{
    public function index(Request $request, OrderProductSearch $search): JsonResponse
    {
        Gate::authorize('create', Order::class);

        $query = $request->query('q');
        $found = $search->find(is_string($query) ? $query : '');

        return response()->json([
            'total' => $found['total'],
            'products' => array_map(fn (array $line): array => (new OrderStockProductResource($line))->resolve($request), $found['products']),
        ]);
    }
}

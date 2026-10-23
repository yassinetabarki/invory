<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\Products\UpdateFinishedStock;
use App\Http\Requests\Products\UpdateFinishedStockRequest;
use App\Models\Product;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;

/**
 * "Update stock" on the product page (S-56-c). `{product}` is route-model bound; a refusal ("the sizes have changed") is a validation
 * error, which sends the person back to where the window was, with it.
 */
final class ProductStockController extends Controller
{
    public function update(UpdateFinishedStockRequest $request, Product $product, UpdateFinishedStock $updateStock): RedirectResponse
    {
        $updateStock->handle($product, $request->unitsBySizeId());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Stock updated.')]);

        return to_route('products.show', $product);
    }
}

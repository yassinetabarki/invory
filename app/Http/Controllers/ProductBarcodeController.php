<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\Barcodes\GenerateBarcodes;
use App\Http\Requests\Barcodes\GenerateBarcodesRequest;
use App\Models\Product;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;

/**
 * "Generate" on the product's Barcodes tab (INV-8-c). `{product}` is route-model bound; a refusal is a validation error on `variants`,
 * which sends the person back to where the window was, with it. The toast is the server's own sentence (it depends on what was
 * already made), so the page shows it as it comes.
 */
final class ProductBarcodeController extends Controller
{
    public function store(GenerateBarcodesRequest $request, Product $product, GenerateBarcodes $generate): RedirectResponse
    {
        $result = $generate->handle($product, $request->variants(), $request->user() ?? abort(401));

        Inertia::flash('toast', ['type' => 'success', 'message' => $result->message()]);

        return to_route('products.show', [$product, 'tab' => 'barcodes']);
    }
}

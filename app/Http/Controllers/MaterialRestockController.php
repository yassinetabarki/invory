<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\Materials\RestockMaterial;
use App\Http\Requests\Materials\StoreRestockRequest;
use App\Models\Material;
use App\Models\User;
use Brick\Math\BigDecimal;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;

final class MaterialRestockController extends Controller
{
    /** StoreRestockRequest authorizes `update` on the material and validates the receipt. */
    public function store(StoreRestockRequest $request, Material $material, RestockMaterial $restockMaterial): RedirectResponse
    {
        $user = $request->user();
        $material = $restockMaterial->handle($material, $request->validated(), $user instanceof User ? $user : abort(401));

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Restocked :name: +:quantity :unit.', [
            'name' => $material->name,
            // "500", not "500.0000": the toast reads like a sentence.
            'quantity' => (string) BigDecimal::of($request->validated('quantity'))->strippedOfTrailingZeros(),
            'unit' => $material->unit->code,
        ])]);

        return back();
    }
}

<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\Materials\StockTotalRequest;
use App\Models\Colour;
use App\Models\Material;
use App\Services\Materials\MaterialStockSentences;
use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;

/**
 * The total of a switch back to one stock, worked out from the typed quantities so that the screen never adds numbers (INV-21-g):
 * `{ total: "290.0000", sentence: "Black 40 m + Indigo 250 m become one stock of 290 m." }`, colours in the order of Settings › Colours.
 */
final class MaterialStockTotalController extends Controller
{
    public function __invoke(StockTotalRequest $request): JsonResponse
    {
        Gate::authorize('viewAny', Material::class);

        $quantities = $request->quantities();
        $total = BigDecimal::zero();
        $colours = [];

        foreach (Colour::query()->ordered()->whereIn('id', array_keys($quantities))->get() as $colour) {
            $total = $total->plus($quantities[$colour->id]);
            $colours[] = ['name' => $colour->name, 'quantity' => $quantities[$colour->id]];
        }

        return response()->json([
            'total' => (string) $total->toScale(4, RoundingMode::HalfUp),
            'sentence' => MaterialStockSentences::oneStock($colours, $total, $request->unitCode()),
        ]);
    }
}

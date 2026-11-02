<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Resources\RestockLineResource;
use App\Models\ProductRestock;
use App\Services\Restocks\RestockBarcodeLookup;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

/**
 * New restock's scan field (INV-3, AC-4; ADR-0009 rule 5): `GET restocks/barcode?number=` answers what the number stands for, with
 * each matching product's line, so a scan of a product not yet on the restock adds it without a second request. Read-only.
 */
final class RestockBarcodeController extends Controller
{
    public function show(Request $request, RestockBarcodeLookup $lookup): JsonResponse
    {
        Gate::authorize('create', ProductRestock::class);

        $found = $lookup->find(mb_substr((string) $request->query('number', ''), 0, 64));

        if ($found['result'] === 'found') {
            $found['matches'] = array_map(fn (array $line): array => (new RestockLineResource($line))->resolve($request), $found['matches']);
        }

        return response()->json($found);
    }
}

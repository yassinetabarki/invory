<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Resources\MaterialResource;
use App\Models\Material;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

/** The data of the Restock sheet for pages that don't carry the material (the toast's "Restock Light wash" action, INV-21-p): read-only JSON. */
final class MaterialRestockSheetController extends Controller
{
    public function __invoke(Request $request, Material $material): JsonResponse
    {
        Gate::authorize('view', $material);
        Material::loadColours([$material]);

        return response()->json(['material' => MaterialResource::make($material)->resolve($request)]);
    }
}

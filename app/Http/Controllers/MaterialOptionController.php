<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\Materials\MaterialOptionsRequest;
use App\Http\Resources\MaterialOptionResource;
use App\Models\Material;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;

/**
 * The BOM picker's typeahead (ADR-0009 rule 5): a small session-authenticated JSON route instead of every material in a page prop.
 */
final class MaterialOptionController extends Controller
{
    public function index(MaterialOptionsRequest $request): AnonymousResourceCollection
    {
        Gate::authorize('viewAny', Material::class);

        // The picker shows the unit (always eager-loaded) and never the category: skip that query.
        $options = fn () => Material::query()->without('category')->orderBy('name')->orderBy('id');

        $matches = $options()->search($request->term())->limit($request->limit())->get();

        // The materials already picked come along even when they don't match: soft-deleted ones are not found.
        $missing = array_values(array_diff($request->included(), $matches->modelKeys()));

        if ($missing !== []) {
            $matches = $matches->concat($options()->whereIn('id', $missing)->get());
        }

        return MaterialOptionResource::collection($matches);
    }
}

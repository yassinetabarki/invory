<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\Materials\StoreRestockRequest;
use App\Models\Material;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;

/**
 * MOCK write (ADR-0006): the restock is validated for real by StoreRestockRequest (which also authorizes the preview
 * ability and `update` on the material), then nothing is saved. S-16-BE replaces the body with a real restock action.
 */
final class MaterialRestockController extends Controller
{
    public function store(StoreRestockRequest $request, Material $material): RedirectResponse
    {
        Inertia::flash('toast', ['type' => 'info', 'message' => __("Preview: the restock passed validation but wasn't saved.")]);

        return back();
    }
}

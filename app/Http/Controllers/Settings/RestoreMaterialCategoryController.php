<?php

declare(strict_types=1);

namespace App\Http\Controllers\Settings;

use App\Actions\Reference\RestoreMaterialCategory;
use App\Http\Controllers\Controller;
use App\Models\MaterialCategory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

/**
 * Restores a material category (ADR-0008). A separate invokable controller: the resource controller keeps to the standard methods.
 */
final class RestoreMaterialCategoryController extends Controller
{
    public function __invoke(MaterialCategory $materialCategory, RestoreMaterialCategory $action): RedirectResponse
    {
        Gate::authorize('update', $materialCategory);

        $action->handle($materialCategory);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Category restored.')]);

        return back();
    }
}

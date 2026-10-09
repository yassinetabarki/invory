<?php

declare(strict_types=1);

namespace App\Http\Controllers\Settings;

use App\Actions\Reference\RestoreMaterialCategory;
use App\Http\Controllers\Controller;
use App\Http\Requests\Reference\RestoreMaterialCategoryRequest;
use App\Models\MaterialCategory;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;

/**
 * Restores a material category (ADR-0008). Refused with an error on `name` while an active
 * row has the same name (the request). A separate invokable controller: the resource controller keeps to the standard methods.
 */
final class RestoreMaterialCategoryController extends Controller
{
    public function __invoke(RestoreMaterialCategoryRequest $request, MaterialCategory $materialCategory, RestoreMaterialCategory $action): RedirectResponse
    {
        $action->handle($materialCategory);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Category restored.')]);

        return back();
    }
}

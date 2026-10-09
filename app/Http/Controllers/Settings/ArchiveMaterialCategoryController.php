<?php

declare(strict_types=1);

namespace App\Http\Controllers\Settings;

use App\Actions\Reference\ArchiveMaterialCategory;
use App\Http\Controllers\Controller;
use App\Models\MaterialCategory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

/**
 * Archives a material category (ADR-0008). A separate invokable controller: the resource controller keeps to the standard methods.
 */
final class ArchiveMaterialCategoryController extends Controller
{
    public function __invoke(MaterialCategory $materialCategory, ArchiveMaterialCategory $action): RedirectResponse
    {
        Gate::authorize('update', $materialCategory);

        $action->handle($materialCategory);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Category archived.')]);

        return back();
    }
}

<?php

declare(strict_types=1);

namespace App\Http\Controllers\Settings;

use App\Actions\Reference\CreateMaterialCategory;
use App\Actions\Reference\DeleteMaterialCategory;
use App\Actions\Reference\UpdateMaterialCategory;
use App\Exceptions\ReferenceInUseException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Reference\StoreMaterialCategoryRequest;
use App\Http\Requests\Reference\UpdateMaterialCategoryRequest;
use App\Models\MaterialCategory;
use App\Services\Reference\ReferenceData;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Settings > Material categories (ADR-0008). Any verified user manages them (decision Q3); every route goes through
 * MaterialCategoryPolicy.
 */
final class MaterialCategoryController extends Controller
{
    public function index(): Response
    {
        Gate::authorize('viewAny', MaterialCategory::class);

        return Inertia::render('settings/material-categories', [
            // `usage_count` = non-deleted materials in the category.
            'categories' => fn (): array => MaterialCategory::query()
                ->withCount('materials')
                ->ordered()
                ->get()
                ->map(fn (MaterialCategory $category): array => [...ReferenceData::category($category), 'usage_count' => $category->materials_count])
                ->all(),
        ]);
    }

    public function store(StoreMaterialCategoryRequest $request, CreateMaterialCategory $createCategory): RedirectResponse
    {
        $createCategory->handle($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Category created.')]);

        return back();
    }

    public function update(UpdateMaterialCategoryRequest $request, MaterialCategory $materialCategory, UpdateMaterialCategory $updateCategory): RedirectResponse
    {
        $updateCategory->handle($materialCategory, $request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Category updated.')]);

        return back();
    }

    public function destroy(MaterialCategory $materialCategory, DeleteMaterialCategory $deleteCategory): RedirectResponse
    {
        Gate::authorize('delete', $materialCategory);

        try {
            $deleteCategory->handle($materialCategory);
        } catch (ReferenceInUseException $e) {
            Inertia::flash('toast', ['type' => 'error', 'message' => $e->getMessage()]);

            return back();
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Category deleted.')]);

        return back();
    }
}

<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\Materials\CreateMaterial;
use App\Actions\Materials\DeleteMaterial;
use App\Actions\Materials\UpdateMaterial;
use App\Enums\MaterialCategory;
use App\Enums\UnitOfMeasure;
use App\Http\Requests\Materials\IndexMaterialRequest;
use App\Http\Requests\Materials\StoreMaterialRequest;
use App\Http\Requests\Materials\UpdateMaterialRequest;
use App\Http\Resources\MaterialResource;
use App\Mock\PreviewHistory;
use App\Mock\PreviewMaterials;
use App\Mock\PreviewProducts;
use App\Models\Material;
use App\Services\Inventory\InventorySummary;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Materials are real (CRUD on the database). The parts that depend on tables that do not exist yet (used-in counts,
 * photos, usage, stock history) come from App\Mock (ADR-0006, removed by S-17).
 */
final class MaterialController extends Controller
{
    public function index(IndexMaterialRequest $request, InventorySummary $inventory): Response
    {
        Gate::authorize('viewAny', Material::class);

        $filters = $request->filters();
        $totals = $inventory->summarize();

        $materials = Material::query()
            ->search($filters['search'])
            ->category($request->category())
            ->when($filters['low_stock'], fn ($query) => $query->needsRestock())
            ->sortedBy($filters['sort'])
            ->paginate(config('invory.per_page'))
            ->withQueryString();

        // MOCK (ADR-0006): used_in_count and photo_url have no source yet.
        PreviewMaterials::decorate($materials);

        return Inertia::render('materials/index', [
            'materials' => MaterialResource::collection($materials),
            'filters' => $filters,
            'categories' => MaterialCategory::options(),
            // Over all non-deleted materials, not just this page.
            'summary' => [
                'materials_count' => $totals['materials_count'],
                'inventory_value' => $totals['inventory_value'],
            ],
            // MOCK (ADR-0006): the newest 5 movements of each material on this page, loaded after the first paint.
            'recent_history' => Inertia::defer(fn (): array => PreviewHistory::recent($materials->items())),
        ]);
    }

    public function create(): Response
    {
        Gate::authorize('create', Material::class);

        return Inertia::render('materials/create', [
            'categories' => MaterialCategory::options(),
            'units' => UnitOfMeasure::options(),
        ]);
    }

    public function store(StoreMaterialRequest $request, CreateMaterial $createMaterial): RedirectResponse
    {
        // Photos are a preview (ADR-0006): validated, but neither stored nor part of the material.
        $material = $createMaterial->handle($request->safe()->except(['photo', 'remove_photo']));

        Inertia::flash('toast', ['type' => 'success', 'message' => $this->toastMessage($request, __('Material created.'))]);

        return to_route('materials.show', $material);
    }

    public function show(Material $material): Response
    {
        Gate::authorize('view', $material);

        // MOCK (ADR-0006): the usage and the history come from the fixture, costed by the production domain code.
        PreviewMaterials::decorate([$material]);

        return Inertia::render('materials/show', [
            'material' => MaterialResource::make($material),
            'usage' => PreviewProducts::load()->usageOf($material),
            'history' => PreviewHistory::byMaterial([$material])[$material->id] ?? [],
        ]);
    }

    public function edit(Material $material): Response
    {
        Gate::authorize('update', $material);

        PreviewMaterials::decorate([$material]);

        return Inertia::render('materials/edit', [
            'categories' => MaterialCategory::options(),
            'units' => UnitOfMeasure::options(),
            'material' => MaterialResource::make($material),
        ]);
    }

    public function update(UpdateMaterialRequest $request, Material $material, UpdateMaterial $updateMaterial): RedirectResponse
    {
        $updateMaterial->handle($material, $request->safe()->except(['photo', 'remove_photo']));

        Inertia::flash('toast', ['type' => 'success', 'message' => $this->toastMessage($request, __('Material updated.'))]);

        return to_route('materials.show', $material);
    }

    public function destroy(Material $material, DeleteMaterial $deleteMaterial): RedirectResponse
    {
        Gate::authorize('delete', $material);

        $deleteMaterial->handle($material);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Material deleted.')]);

        return to_route('materials.index');
    }

    /**
     * MOCK (ADR-0006): says so when a photo was sent (or removed), because photos are not saved yet.
     */
    private function toastMessage(StoreMaterialRequest|UpdateMaterialRequest $request, string $message): string
    {
        if ($request->hasFile('photo') || $request->boolean('remove_photo')) {
            return $message.' '.__("Photos are a preview and weren't saved.");
        }

        return $message;
    }
}

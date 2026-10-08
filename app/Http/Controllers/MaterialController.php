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
use App\Models\Material;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

final class MaterialController extends Controller
{
    public function index(IndexMaterialRequest $request): Response
    {
        Gate::authorize('viewAny', Material::class);

        $filters = $request->filters();

        $materials = Material::query()
            ->search($filters['search'])
            ->category($request->category())
            ->when($filters['low_stock'], fn ($query) => $query->needsRestock())
            ->sortedBy($filters['sort'])
            ->paginate(config('invory.per_page'))
            ->withQueryString();

        return Inertia::render('materials/index', [
            'materials' => MaterialResource::collection($materials),
            'filters' => $filters,
            'categories' => MaterialCategory::options(),
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
        $material = $createMaterial->handle($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Material created.')]);

        return to_route('materials.show', $material);
    }

    public function show(Material $material): Response
    {
        Gate::authorize('view', $material);

        return Inertia::render('materials/show', [
            'material' => MaterialResource::make($material),
        ]);
    }

    public function edit(Material $material): Response
    {
        Gate::authorize('update', $material);

        return Inertia::render('materials/edit', [
            'categories' => MaterialCategory::options(),
            'units' => UnitOfMeasure::options(),
            'material' => MaterialResource::make($material),
        ]);
    }

    public function update(UpdateMaterialRequest $request, Material $material, UpdateMaterial $updateMaterial): RedirectResponse
    {
        $updateMaterial->handle($material, $request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Material updated.')]);

        return to_route('materials.show', $material);
    }

    public function destroy(Material $material, DeleteMaterial $deleteMaterial): RedirectResponse
    {
        Gate::authorize('delete', $material);

        $deleteMaterial->handle($material);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Material deleted.')]);

        return to_route('materials.index');
    }
}

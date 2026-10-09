<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\Materials\CreateMaterial;
use App\Actions\Materials\DeleteMaterial;
use App\Actions\Materials\UpdateMaterial;
use App\Exceptions\MaterialInUseException;
use App\Http\Requests\Materials\IndexMaterialRequest;
use App\Http\Requests\Materials\StoreMaterialRequest;
use App\Http\Requests\Materials\UpdateMaterialRequest;
use App\Http\Resources\MaterialResource;
use App\Mock\PreviewHistory;
use App\Mock\PreviewMaterials;
use App\Mock\PreviewProducts;
use App\Models\Material;
use App\Models\User;
use App\Services\Inventory\InventorySummary;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Materials are real (CRUD on the database, used-in counts from the real compositions). The parts that depend on tables that
 * do not exist yet (photos, usage, stock history) come from App\Mock (ADR-0006, removed by S-17).
 */
final class MaterialController extends Controller
{
    public function index(IndexMaterialRequest $request, InventorySummary $inventory): Response
    {
        Gate::authorize('viewAny', Material::class);

        $filters = $request->filters();

        // Every prop that costs a query or a computation is a closure: Inertia runs it only when the response includes it
        // (a full visit, or a partial reload that lists it). The page is shared by `materials` and `recent_history`.
        /** @var LengthAwarePaginator<int, Material>|null $page */
        $page = null;
        $materials = function () use (&$page, $request, $filters): LengthAwarePaginator {
            if ($page === null) {
                $page = Material::query()
                    ->withUsedInCount()
                    ->search($filters['search'])
                    ->inCategory($request->category())
                    ->when($filters['low_stock'], fn ($query) => $query->needsRestock())
                    ->sortedBy($filters['sort'])
                    ->paginate(config('invory.per_page'))
                    ->withQueryString();

                // MOCK (ADR-0006): photo_url has no source yet.
                PreviewMaterials::decorate($page);
            }

            return $page;
        };

        return Inertia::render('materials/index', [
            'materials' => fn (): AnonymousResourceCollection => MaterialResource::collection($materials()),
            'filters' => $filters,
            // Over all non-deleted materials, not just this page: a full scan, so it must stay lazy.
            'summary' => function () use ($inventory): array {
                $totals = $inventory->summarize();

                return [
                    'materials_count' => $totals['materials_count'],
                    'inventory_value' => $totals['inventory_value'],
                ];
            },
            // MOCK (ADR-0006): the newest 5 movements of each material on this page, loaded after the first paint.
            'recent_history' => Inertia::defer(fn (): array => PreviewHistory::recent($materials()->items())),
        ]);
    }

    public function create(): Response
    {
        Gate::authorize('create', Material::class);

        // Category and unit options come from the shared `reference` prop (ADR-0008).
        return Inertia::render('materials/create');
    }

    public function store(StoreMaterialRequest $request, CreateMaterial $createMaterial): RedirectResponse
    {
        // Photos are a preview (ADR-0006): validated, but neither stored nor part of the material.
        $material = $createMaterial->handle($request->safe()->except(['photo', 'remove_photo']), $this->actor($request));

        Inertia::flash('toast', ['type' => 'success', 'message' => $this->toastMessage($request, __('Material created.'))]);

        return to_route('materials.show', $material);
    }

    public function show(Material $material): Response
    {
        Gate::authorize('view', $material);

        // MOCK (ADR-0006): the photo, and the history, come from the fixture. The usage is real, costed by ProductCostingService.
        PreviewMaterials::decorate([$material]);

        return Inertia::render('materials/show', [
            'material' => MaterialResource::make($material),
            'usage' => fn (): array => PreviewProducts::load()->usageOf($material),
            'history' => fn (): array => PreviewHistory::byMaterial([$material])[$material->id] ?? [],
        ]);
    }

    public function edit(Material $material): Response
    {
        Gate::authorize('update', $material);

        PreviewMaterials::decorate([$material]);

        return Inertia::render('materials/edit', [
            'material' => MaterialResource::make($material),
        ]);
    }

    public function update(UpdateMaterialRequest $request, Material $material, UpdateMaterial $updateMaterial): RedirectResponse
    {
        $updateMaterial->handle($material, $request->safe()->except(['photo', 'remove_photo']), $this->actor($request));

        Inertia::flash('toast', ['type' => 'success', 'message' => $this->toastMessage($request, __('Material updated.'))]);

        return to_route('materials.show', $material);
    }

    public function destroy(Material $material, DeleteMaterial $deleteMaterial): RedirectResponse
    {
        Gate::authorize('delete', $material);

        try {
            $deleteMaterial->handle($material);
        } catch (MaterialInUseException $e) {
            // The message names the products that still use the material. Nothing was deleted.
            Inertia::flash('toast', ['type' => 'error', 'message' => $e->getMessage()]);

            return back();
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Material deleted.')]);

        return to_route('materials.index');
    }

    /** The signed-in user, for the audit columns. The `auth` middleware and the request authorization guarantee one. */
    private function actor(Request $request): User
    {
        $user = $request->user();

        return $user instanceof User ? $user : abort(401);
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

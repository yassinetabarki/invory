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
use App\Http\Resources\MaterialUsageResource;
use App\Http\Resources\StockMovementResource;
use App\Models\Material;
use App\Models\User;
use App\Services\Costing\MaterialUsageService;
use App\Services\Inventory\InventorySummary;
use App\Services\Inventory\StockHistory;
use App\Services\Reference\ReferenceData;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Materials are real: CRUD on the database (photos on the public disk through the Actions), used-in counts and usage from the
 * real compositions, the stock history from the ledger.
 */
final class MaterialController extends Controller
{
    public function index(IndexMaterialRequest $request, InventorySummary $inventory, StockHistory $history): Response
    {
        Gate::authorize('viewAny', Material::class);

        $filters = $request->filters();

        // Every prop that costs a query or a computation is a closure: Inertia runs it only when the response includes it
        // (a full visit, or a partial reload that lists it). The page is shared by `materials` and `recent_history`.
        /** @var LengthAwarePaginator<int, Material>|null $page */
        $page = null;
        /** @var list<int> $pageIds the ids of the page, read before MaterialResource::collection() replaces the paginator's items */
        $pageIds = [];
        $materials = function () use (&$page, &$pageIds, $request, $filters): LengthAwarePaginator {
            if ($page === null) {
                $page = Material::query()
                    ->withUsedInCount()
                    ->search($filters['search'])
                    ->inCategory($request->category())
                    ->when($filters['low_stock'], fn ($query) => $query->needsRestock())
                    ->sortedBy($filters['sort'])
                    ->paginate(config('invory.per_page'))
                    ->withQueryString();

                $pageIds = array_values(array_map(static fn (Material $material): int => $material->id, $page->items()));
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
            // The newest 5 movements of each material on this page, in one query, loaded after the first paint.
            'recent_history' => Inertia::defer(function () use ($history, $materials, &$pageIds): AnonymousResourceCollection {
                $materials();

                return StockMovementResource::collection($history->recentFor($pageIds));
            }),
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
        $material = $createMaterial->handle($request->validated(), $this->actor($request));

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Material created.')]);

        return to_route('materials.show', $material);
    }

    public function show(Material $material, StockHistory $history, ReferenceData $reference, MaterialUsageService $usage): Response
    {
        Gate::authorize('view', $material);

        return Inertia::render('materials/show', [
            'material' => MaterialResource::make($material),
            // The units are the ones the shared `reference` prop reads: one query for both (the query budget).
            'usage' => function () use ($usage, $material, $reference): AnonymousResourceCollection {
                $rows = $usage->for($material, $reference->units());

                return MaterialUsageResource::collection($rows);
            },
            'history' => fn (): AnonymousResourceCollection => StockMovementResource::collection($history->forMaterial($material)),
        ]);
    }

    public function edit(Material $material): Response
    {
        Gate::authorize('update', $material);

        return Inertia::render('materials/edit', [
            'material' => MaterialResource::make($material),
        ]);
    }

    public function update(UpdateMaterialRequest $request, Material $material, UpdateMaterial $updateMaterial): RedirectResponse
    {
        $updateMaterial->handle($material, $request->validated(), $this->actor($request));

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Material updated.')]);

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
}

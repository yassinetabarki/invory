<?php

declare(strict_types=1);

namespace App\Http\Controllers\Settings;

use App\Actions\Sizes\CreateSize;
use App\Actions\Sizes\UpdateSize;
use App\Http\Controllers\Controller;
use App\Http\Requests\Sizes\StoreSizeRequest;
use App\Http\Requests\Sizes\UpdateSizeRequest;
use App\Models\Product;
use App\Models\Size;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Settings > Sizes (INV-18-d, ADR-0028). Any member manages the workshop's sizes, through SizePolicy. A size is never deleted or
 * archived, and One size (the flagged one) is renamed like any other.
 *
 * The whole list in one page, in list order (position, id): a workshop has tens of sizes, never pages of them.
 */
final class SizeController extends Controller
{
    public function index(): Response
    {
        Gate::authorize('viewAny', Size::class);

        return Inertia::render('settings/sizes', [
            'sizes' => fn (): array => Size::query()
                // "Used by": the non-deleted products (any status) that have the size, one correlated count for the whole list.
                ->addSelect(['usage_count' => Product::query()->selectRaw('count(*)')
                    ->join('product_sizes', 'product_sizes.product_id', '=', 'products.id')
                    ->whereColumn('product_sizes.size_id', 'sizes.id')])
                ->ordered()
                ->get()
                ->map(fn (Size $size): array => [
                    'id' => $size->id,
                    'label' => $size->label,
                    'one_size' => $size->is_one_size,
                    'position' => $size->position,
                    'usage_count' => (int) $size->getAttribute('usage_count'),
                ])
                ->all(),
        ]);
    }

    public function store(StoreSizeRequest $request, CreateSize $createSize): RedirectResponse
    {
        $createSize->handle($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Size created.')]);

        return back();
    }

    public function update(UpdateSizeRequest $request, Size $size, UpdateSize $updateSize): RedirectResponse
    {
        $updateSize->handle($size, $request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Size updated.')]);

        return back();
    }
}

<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\Production\ProgressIndexRequest;
use App\Http\Resources\ColourResource;
use App\Http\Resources\ProgressBatchResource;
use App\Http\Resources\ProgressProductCardResource;
use App\Http\Resources\RestockProductRefResource;
use App\Models\Colour;
use App\Models\Product;
use App\Services\Production\ProductionPath;
use App\Services\Production\ProgressBook;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Progress (INV-5-f, AC-3 and AC-4, ADR-0023): the batches the workshop is making for its shelf. Without `?phase=` the tiles and a card
 * per product ("All phases"); with it, the view of that phase. An unknown phase is a 404.
 */
final class ProgressController extends Controller
{
    public function index(ProgressIndexRequest $request, ProgressBook $progress): Response
    {
        $filters = $request->filters();
        $path = ProductionPath::current();
        $phase = $filters['phase'];

        if ($phase !== null && ! $path->has($phase)) {
            abort(404);
        }

        // The figures are deferred in one rescued group: the frame (header, tiles' frames, filters) shows at once, and one "Try again"
        // reloads them all.
        $defer = static fn (callable $value): mixed => Inertia::defer($value, 'progress', rescue: true);

        return Inertia::render('progress/index', [
            'path' => array_map(static fn (array $step): array => ['phase' => $step['ref'], 'name' => $step['name']], $path->steps),
            'filters' => $filters,
            'product_filter' => fn (): ?array => $filters['product'] === null ? null : self::productRef($filters['product']),
            'colours' => fn (): array => ColourResource::collection(Colour::query()->ordered()->get())->resolve(),
            'tiles' => $defer(fn (): array => $progress->tiles($path, $filters['product'], $filters['colour'])),
            'all_units' => $defer(fn (): int => $progress->units($filters['product'], $filters['colour'])),
            'total_units' => $defer(fn (): int => $progress->units()),
            'has_batches' => $defer(fn (): bool => $progress->hasBatches()),
            'products' => $defer(fn (): ?array => $phase !== null ? null : ProgressProductCardResource::collection(
                $progress->products($path, $filters['product'], $filters['colour']),
            )->response()->getData(true)),
            'phase_view' => $defer(fn (): ?array => $phase === null ? null : self::phaseView($path, $phase, $progress, $filters['product'], $filters['colour'])),
        ]);
    }

    /** @return array<string, mixed> */
    private static function phaseView(ProductionPath $path, string|int $phase, ProgressBook $progress, ?int $product, ?int $colour): array
    {
        $view = $progress->phase($phase, $product, $colour);
        $step = static fn (?array $step): ?array => $step === null ? null : ['phase' => $step['ref'], 'name' => $step['name']];

        return [
            'phase' => ['phase' => $phase, 'name' => $path->name($phase)],
            'previous' => $step($path->previous($phase)),
            'next' => $step($path->next($phase)),
            'units' => $view['units'],
            'batch_count' => $view['batch_count'],
            'product_count' => $view['product_count'],
            'oldest_since' => $view['oldest_since'],
            'batches' => ProgressBatchResource::collection($view['batches'])->response()->getData(true),
        ];
    }

    /** @return array<string, mixed>|null */
    private static function productRef(int $id): ?array
    {
        $product = Product::withTrashed()->find($id);

        return $product === null ? null : (new RestockProductRefResource($product))->resolve();
    }
}

<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\Restocks\RecordRestock;
use App\Http\Requests\Restocks\IndexRestockRequest;
use App\Http\Requests\Restocks\StoreRestockRequest;
use App\Http\Resources\RestockDetailResource;
use App\Http\Resources\RestockLineResource;
use App\Http\Resources\RestockProductRefResource;
use App\Http\Resources\RestockSummaryResource;
use App\Models\Product;
use App\Models\ProductRestock;
use App\Models\User;
use App\Services\Restocks\RestockBook;
use App\Services\Restocks\RestockMessages;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Restocks of finished products (INV-3): the Restocks page with a restock's detail, New restock and saving it (Undo is
 * `RestockUndoController`). `ProductRestockPolicy`
 * on every route. A restock is never edited or deleted.
 */
final class RestockController extends Controller
{
    public function index(IndexRestockRequest $request, RestockBook $restocks): Response
    {
        $filters = $request->filters();

        // Every prop that costs a query is a closure, so a partial reload skips what it does not list: opening a restock reloads
        // `restock` alone.
        return Inertia::render('restocks/index', [
            'restocks' => fn (): AnonymousResourceCollection => RestockSummaryResource::collection(
                $restocks->page($filters['search'], $filters['product'], (int) config('invory.per_page')),
            ),
            'filters' => $filters,
            'product_filter' => fn (): ?array => $filters['product'] === null ? null : self::productRef($filters['product']),
            'has_restocks' => fn (): bool => $restocks->hasRestocks(),
            'restock' => fn (): ?array => $filters['restock'] === null ? null : self::detail($filters['restock'], $restocks),
        ]);
    }

    /** New restock (AC-3); from a product page (`?product=`) that product's line is already there (AC-7). */
    public function create(Request $request, RestockBook $restocks): Response
    {
        Gate::authorize('create', ProductRestock::class);

        $id = $request->integer('product');
        $product = $id > 0 ? Product::query()->find($id) : null;

        return Inertia::render('restocks/create', [
            'preset' => $product === null ? null : (new RestockLineResource($restocks->line($product)))->resolve(),
        ]);
    }

    /**
     * Saving (AC-1, AC-3): the Restocks list with the new restock on top and "34 units restocked."; from a product's page, that page
     * (AC-7).
     */
    public function store(StoreRestockRequest $request, RecordRestock $record): RedirectResponse
    {
        $user = $request->user();
        $data = $request->restock();
        $restock = $record->handle($data, $user instanceof User ? $user : abort(401));

        // Started from a product's page (AC-7): back to it, naming the product when the restock was that product alone (otherwise
        // the general toast: the human's answer, 2026-11-02).
        $from = $request->fromProduct() === null ? null : Product::query()->find($request->fromProduct());

        if ($from !== null) {
            $alone = array_column($data['products'], 'product_id') === [$from->id];
            Inertia::flash('toast', ['type' => 'success', 'message' => $alone
                ? RestockMessages::productRestocked($restock->units, $from->name)
                : RestockMessages::restocked($restock->units)]);

            return to_route('products.show', $from);
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => RestockMessages::restocked($restock->units)]);

        return to_route('restocks.index');
    }

    /**
     * A product as the Product filter shows it, deleted ones included; null when the workshop has none with that id.
     *
     * @return array<string, mixed>|null
     */
    private static function productRef(int $id): ?array
    {
        $product = Product::withTrashed()->find($id);

        return $product === null ? null : (new RestockProductRefResource($product))->resolve();
    }

    /**
     * The open restock's panel; null when the workshop has none with that id.
     *
     * @return array<string, mixed>|null
     */
    private static function detail(int $id, RestockBook $restocks): ?array
    {
        $restock = ProductRestock::query()->find($id);

        if ($restock === null || Gate::denies('view', $restock)) {
            return null;
        }

        return (new RestockDetailResource($restocks->detail($restock)))->resolve();
    }
}

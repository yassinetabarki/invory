<?php

declare(strict_types=1);

namespace App\Services\Restocks;

use App\Models\Colour;
use App\Models\Product;
use App\Models\ProductRestock;
use App\Models\ProductRestockLine;
use App\Models\Size;
use App\Models\SizeList;
use App\Services\Products\FinishedStock;
use App\Services\Products\FinishedStockLevel;
use App\Services\Products\SizeLists;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;

/**
 * The read side of the restock pages (INV-3): the Restocks list with each restock's products, a restock's detail, a product's line on
 * New restock and the product picker. Read-only; the workshop scope limits every query to the current workshop. Whole units only.
 */
final readonly class RestockBook
{
    public function __construct(private UndoCheck $undoCheck, private SizeLists $sizeLists, private FinishedStock $finishedStock) {}

    /**
     * The Restocks page (AC-2): matching the search (reference, note, or a product's name, deleted products included) and the product,
     * newest first, 25 a page, each with who recorded it and its products (see {@see productsOf()}).
     *
     * @return LengthAwarePaginator<int, ProductRestock>
     */
    public function page(?string $search, ?int $productId, int $perPage): LengthAwarePaginator
    {
        $page = ProductRestock::query()
            ->with('recorder:id,name')
            ->when($search !== null, fn (Builder $query) => $query->where(fn (Builder $q) => $q
                ->whereLike('reference', "%{$search}%")
                ->orWhereLike('note', "%{$search}%")
                ->orWhereIn('id', ProductRestockLine::query()->select('product_restock_id')
                    ->whereIn('product_id', Product::withTrashed()->whereLike('name', "%{$search}%")->select('id')))))
            ->when($productId !== null, fn (Builder $query) => $query
                ->whereIn('id', ProductRestockLine::query()->select('product_restock_id')->where('product_id', $productId)))
            ->newestFirst()
            ->paginate($perPage)
            ->withQueryString();

        $products = $this->productsOf(new EloquentCollection($page->items()));

        foreach ($page->items() as $restock) {
            $restock->setRelation('productTotals', collect($products[$restock->id] ?? []));
        }

        return $page;
    }

    /** True when the workshop has any restock at all: tells "No restocks yet" from "No restock matches". */
    public function hasRestocks(): bool
    {
        return ProductRestock::query()->exists();
    }

    /**
     * Each restock's products in the order they were added, with the units each got: two queries for a whole page.
     *
     * @param  EloquentCollection<int, ProductRestock>  $restocks
     * @return array<int, list<array{product: Product, units: int}>> by restock id
     */
    public function productsOf(EloquentCollection $restocks): array
    {
        if ($restocks->isEmpty()) {
            return [];
        }

        $sums = ProductRestockLine::query()
            ->whereIn('product_restock_id', $restocks->modelKeys())
            ->groupBy('product_restock_id', 'product_id')
            ->orderBy('product_restock_id')
            ->selectRaw('product_restock_id, product_id, MIN(position) AS first_position, SUM(units) AS total_units')
            ->toBase()
            ->get();

        $names = Product::withTrashed()->whereKey($sums->pluck('product_id')->unique()->all())->get()->keyBy('id');
        $byRestock = [];

        foreach ($sums->sortBy(fn (object $row): array => [(int) $row->product_restock_id, (int) $row->first_position]) as $row) {
            $product = $names->get((int) $row->product_id);

            if ($product !== null) {
                $byRestock[(int) $row->product_restock_id][] = ['product' => $product, 'units' => (int) $row->total_units];
            }
        }

        return $byRestock;
    }

    /**
     * A restock's detail (AC-5): its products in the order added, each with the colours that got units (list order) and every size of
     * the list those units were in, the totals, who recorded and undid it, and why it can't be undone (null when it can, or is undone).
     *
     * @return array{restock: ProductRestock, products: list<array{product: Product, units: int}>, blocks: list<array{product: Product, sizes: list<Size>, one_size: bool, units: int, rows: list<array{colour: Colour, units: list<int>, total: int}>, totals: list<int>|null}>, undo_refusal: string|null}
     */
    public function detail(ProductRestock $restock): array
    {
        $restock->loadMissing(['recorder:id,name', 'undoer:id,name']);
        $lines = $restock->lines()->with(['product', 'colour', 'size.sizeList.sizes'])->get();

        $blocks = [];

        foreach ($lines->groupBy('product_id')->sortBy(fn (Collection $group): int => (int) $group->min('position')) as $group) {
            /** @var ProductRestockLine $first */
            $first = $group->first();
            $sizeList = $first->size->sizeList;
            $sizes = array_values($sizeList->sizes->sortBy('position')->all());
            $rows = [];

            foreach ($group->groupBy('colour_id')->sortBy(fn (Collection $byColour): array => [
                $byColour->first()->colour->position, $byColour->first()->colour->name, $byColour->first()->colour_id,
            ]) as $byColour) {
                $bySize = $byColour->mapWithKeys(fn (ProductRestockLine $line): array => [$line->size_id => $line->units])->all();
                $units = array_map(fn (Size $size): int => $bySize[$size->id] ?? 0, $sizes);
                $rows[] = ['colour' => $byColour->first()->colour, 'units' => $units, 'total' => array_sum($units)];
            }

            $totals = null;

            if (count($rows) > 1) {
                $totals = array_map(fn (int $i): int => array_sum(array_map(fn (array $row): int => $row['units'][$i], $rows)), array_keys($sizes));
            }

            $blocks[] = [
                'product' => $first->product,
                'sizes' => $sizes,
                'one_size' => $sizeList->code === SizeList::ONE_SIZE,
                'units' => array_sum(array_column($rows, 'total')),
                'rows' => $rows,
                'totals' => $totals,
            ];
        }

        $products = array_map(fn (array $block): array => ['product' => $block['product'], 'units' => $block['units']], $blocks);

        return [
            'restock' => $restock,
            'products' => $products,
            'blocks' => $blocks,
            'undo_refusal' => $this->undoCheck->refusal($restock),
        ];
    }

    /**
     * A product's line on New restock (AC-3): its sizes, whether it is "One size", and what each colour and size has on the shelf.
     *
     * @return array{product: Product, sizes: list<Size>, one_size: bool, stock: FinishedStockLevel}
     */
    public function line(Product $product): array
    {
        $sizeList = $this->sizeLists->category($product->category_id)->sizeList;

        return [
            'product' => $product,
            'sizes' => array_values($sizeList->sizes->all()),
            'one_size' => $sizeList->code === SizeList::ONE_SIZE,
            'stock' => $this->finishedStock->of($product),
        ];
    }

    /**
     * The product picker (AC-3) and the Product filter (AC-2): non-deleted products of any status matching the search (name or SKU),
     * by name, at most `$limit`, plus the ones asked for by id when they don't match.
     *
     * @param  list<int>  $include
     * @return list<Product>
     */
    public function options(?string $search, int $limit, array $include = []): array
    {
        $matches = Product::query()->search($search)->byName()->limit($limit)->get();
        $missing = array_values(array_diff($include, $matches->modelKeys()));

        if ($missing !== []) {
            $matches = $matches->concat(Product::query()->whereKey($missing)->byName()->get());
        }

        return array_values($matches->all());
    }
}

<?php

declare(strict_types=1);

namespace App\Services\Restocks;

use App\Models\ProductRestock;
use App\Models\ProductRestockLine;
use App\Models\ProductSizeStock;
use App\Models\SizeList;
use Illuminate\Database\Eloquent\Collection;

/**
 * Whether a restock can be undone (INV-3, AC-6, ADR-0022): every colour and size it added must still hold at least that many units on
 * the shelf. Lines of a product deleted since are skipped (its stock went with it). The reason names the first variant short of what
 * it added: products by their place on the restock, colours in list order, sizes in size order.
 *
 * Read without locks for the restock's panel; `UndoRestock` asks again under the products' locks before it writes.
 */
final class UndoCheck
{
    /**
     * The lines that still count (their product isn't deleted), in the order the reason is looked for, with their product, colour and
     * size (and its list) loaded: two queries beyond the restock's lines.
     *
     * @return Collection<int, ProductRestockLine>
     */
    public function linesOf(ProductRestock $restock): Collection
    {
        return $restock->lines()->with(['product', 'colour', 'size.sizeList'])->get()
            ->reject(fn (ProductRestockLine $line): bool => $line->product->trashed())
            ->sortBy(fn (ProductRestockLine $line): array => [$line->position, $line->colour->position, $line->colour->name, $line->colour_id, $line->size->position])
            ->values();
    }

    /**
     * The reason the restock can't be undone, or null when it can (an undone restock has no reason: it has no Undo at all).
     *
     * @param  Collection<int, ProductRestockLine>|null  $lines  from {@see linesOf()}, when the caller already has them
     */
    public function refusal(ProductRestock $restock, ?Collection $lines = null): ?string
    {
        if ($restock->isUndone()) {
            return null;
        }

        $lines ??= $this->linesOf($restock);
        $shelf = $this->shelfOf($lines);

        foreach ($lines as $line) {
            $onShelf = $shelf[$line->product_id.'-'.$line->colour_id.'-'.$line->size_id] ?? 0;

            if ($onShelf < $line->units) {
                return RestockMessages::cantUndo(
                    $line->product->name,
                    $line->colour->name,
                    $line->size->label,
                    $line->size->sizeList->code === SizeList::ONE_SIZE,
                    $onShelf,
                    $line->units,
                );
            }
        }

        return null;
    }

    /**
     * The units on the shelf of every variant the lines name: one query.
     *
     * @param  Collection<int, ProductRestockLine>  $lines
     * @return array<string, int> "product-colour-size" => units
     */
    public function shelfOf(Collection $lines): array
    {
        if ($lines->isEmpty()) {
            return [];
        }

        return ProductSizeStock::query()->whereIn('product_id', $lines->pluck('product_id')->unique()->all())->get()
            ->mapWithKeys(fn (ProductSizeStock $row): array => [$row->product_id.'-'.$row->colour_id.'-'.$row->size_id => $row->units])
            ->all();
    }
}

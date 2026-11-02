<?php

declare(strict_types=1);

namespace App\Actions\Restocks;

use App\Models\Product;
use App\Models\ProductRestock;
use App\Models\ProductSizeStock;
use App\Models\User;
use App\Services\Restocks\RestockMessages;
use App\Services\Restocks\UndoCheck;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Undo restock (INV-3, AC-6, ADR-0022): takes every unit the restock added back off the shelf at once and marks it undone; nothing is
 * deleted. Refused, with nothing changed, when it is already undone or when a variant it added now holds fewer units than it added.
 * A product deleted since is skipped (its stock went with it); the other products' units are taken back.
 *
 * Locks the restock row (so two undos run one after the other), then its products in id order (the lock every writer of finished
 * stock takes), then checks again under the locks.
 */
final readonly class UndoRestock
{
    public function __construct(private UndoCheck $check) {}

    /** @throws ValidationException on `undo` with the reason; nothing is changed */
    public function handle(ProductRestock $restock, User $user): void
    {
        DB::transaction(function () use ($restock, $user): void {
            $locked = ProductRestock::query()->lockForUpdate()->findOrFail($restock->id);

            if ($locked->isUndone()) {
                throw ValidationException::withMessages(['undo' => RestockMessages::alreadyUndone()]);
            }

            $productIds = $locked->lines()->distinct()->pluck('product_id')->map(fn (mixed $id): int => (int) $id)->sort()->values()->all();
            Product::withTrashed()->whereKey($productIds)->orderBy('id')->lockForUpdate()->get();

            $lines = $this->check->linesOf($locked);
            $refusal = $this->check->refusal($locked, $lines);

            if ($refusal !== null) {
                throw ValidationException::withMessages(['undo' => $refusal]);
            }

            foreach ($lines as $line) {
                $row = ProductSizeStock::query()
                    ->where(['product_id' => $line->product_id, 'colour_id' => $line->colour_id, 'size_id' => $line->size_id])
                    ->firstOrFail();

                // A variant at 0 has no row (ADR-0017).
                $row->units === $line->units ? $row->delete() : $row->decrement('units', $line->units);
            }

            $locked->forceFill(['undone_at' => now(), 'undone_by' => $user->id])->save();
        });
    }
}

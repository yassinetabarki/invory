<?php

declare(strict_types=1);

namespace App\Actions\Materials;

use App\Enums\StockMovementType;
use App\Models\Material;
use App\Models\User;
use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Books a receipt of material (costing skill, "Restock and price history"):
 *
 *     quantity_on_hand' = quantity_on_hand + q
 *     unit_cost'        = price paid   when update_unit_cost (the default): the latest price, not an average
 *                       = unit_cost    otherwise
 *
 * One `restock` movement, plus a `price_change` only when the unit cost actually changed, both dated with the receipt date.
 * Everything runs in one transaction under the row lock of the material, so two receipts at once add up instead of one
 * overwriting the other.
 */
final readonly class RestockMaterial
{
    /** The largest balance `materials.quantity_on_hand` (decimal(14,4)) can hold. */
    private const MAX_STOCK = '9999999999.9999';

    public function __construct(
        private LockMaterialForUpdate $lockMaterial,
        private AppendStockMovement $appendMovement,
    ) {}

    /**
     * @param  array<string, mixed>  $data  validated input: `quantity`, `unit_price`, `received_on` (Y-m-d), optional `reference`, optional `update_unit_cost` (true when omitted)
     *
     * @throws ValidationException when the new balance would not fit the stock column
     */
    public function handle(Material $material, array $data, User $actor): Material
    {
        return DB::transaction(function () use ($material, $data, $actor): Material {
            $this->lockMaterial->handle($material);

            $quantity = BigDecimal::of($data['quantity']);
            $price = BigDecimal::of($data['unit_price']);
            $updateUnitCost = (bool) ($data['update_unit_cost'] ?? true);

            $stockAfter = BigDecimal::of($material->quantity_on_hand)->plus($quantity)->toScale(4, RoundingMode::HalfUp);

            if ($stockAfter->isGreaterThan(self::MAX_STOCK)) {
                throw ValidationException::withMessages([
                    'quantity' => __('The stock would exceed :max.', ['max' => self::MAX_STOCK]),
                ]);
            }

            $costBefore = BigDecimal::of($material->unit_cost);
            $costAfter = $updateUnitCost ? $price->toScale(4, RoundingMode::HalfUp) : $costBefore;

            $material->quantity_on_hand = (string) $stockAfter;
            $material->unit_cost = (string) $costAfter;
            $material->updated_by = $actor->id;
            $material->save();

            $receivedOn = (string) $data['received_on'];

            $this->appendMovement->handle($material, StockMovementType::Restock, $receivedOn, $actor, [
                'quantity' => (string) $quantity->toScale(4, RoundingMode::HalfUp),
                'quantity_after' => (string) $stockAfter,
                'unit_price' => (string) $price->toScale(4, RoundingMode::HalfUp),
                'reference' => $data['reference'] ?? null,
            ]);

            if (! $costAfter->isEqualTo($costBefore)) {
                $this->appendMovement->handle($material, StockMovementType::PriceChange, $receivedOn, $actor, [
                    'unit_cost_before' => (string) $costBefore,
                    'unit_cost_after' => (string) $costAfter,
                    'quantity_after' => (string) $stockAfter,
                ]);
            }

            return $material;
        });
    }
}

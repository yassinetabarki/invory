<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\StockMovementType;
use App\Models\Concerns\BelongsToWorkshop;
use Carbon\CarbonInterface;
use Database\Factories\StockMovementFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One entry of a material's stock ledger (ARCHITECTURE §4). Append-only: there is no Action that updates or deletes one.
 *
 * Which columns each type fills:
 * - `created`: `quantity` = opening stock, `quantity_after` = the same, `unit_price` = opening unit cost
 * - `restock`: `quantity` received, `quantity_after` = stock after, `unit_price` paid, `reference`
 * - `correction`: `quantity_before` and `quantity_after`, `note` = the reason
 * - `price_change`: `unit_cost_before` and `unit_cost_after`, `quantity_after` = stock at that moment (the props send null for it)
 * - `cutting` (S-53): `quantity` taken, `quantity_before` and `quantity_after`, plus `order_id`, `product_id` and the `units` cut
 * - `cutting_return` (S-53): the same, `quantity` given back
 *
 * @property int $id
 * @property int $workshop_id The workshop of the material. Never mass-assignable: it comes from the material.
 * @property int $material_id
 * @property StockMovementType $type
 * @property CarbonInterface $occurred_on
 * @property string|null $quantity
 * @property string|null $quantity_before
 * @property string|null $quantity_after
 * @property string|null $unit_price
 * @property string|null $unit_cost_before
 * @property string|null $unit_cost_after
 * @property string|null $reference
 * @property string|null $note
 * @property int|null $order_id The order a `cutting` / `cutting_return` entry is about (only ever soft-deleted).
 * @property int|null $product_id The product of that order line (only ever soft-deleted).
 * @property int|null $units The units of the order line that were cut or moved back.
 * @property int|null $user_id Who did it (null once that user is deleted). Never mass-assignable: it comes from the actor.
 * @property string|null $user_name Not a column: the history queries join the user's name in (one query, no N+1).
 * @property string|null $order_client Not a column: the history queries join the order's client in (soft-deleted orders included).
 * @property string|null $product_name Not a column: the history queries join the product's name in (soft-deleted products included).
 * @property CarbonInterface $created_at
 * @property CarbonInterface $updated_at
 */
#[Fillable([
    'type', 'occurred_on', 'quantity', 'quantity_before', 'quantity_after', 'unit_price', 'unit_cost_before', 'unit_cost_after',
    'reference', 'note', 'order_id', 'product_id', 'units',
])]
class StockMovement extends Model
{
    /** @use HasFactory<StockMovementFactory> */
    use BelongsToWorkshop, HasFactory;

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'type' => StockMovementType::class,
            'occurred_on' => 'date',
            'quantity' => 'decimal:4',
            'quantity_before' => 'decimal:4',
            'quantity_after' => 'decimal:4',
            'unit_price' => 'decimal:4',
            'unit_cost_before' => 'decimal:4',
            'unit_cost_after' => 'decimal:4',
            'units' => 'integer',
        ];
    }

    /** @return BelongsTo<Material, $this> */
    public function material(): BelongsTo
    {
        return $this->belongsTo(Material::class)->withTrashed();
    }

    /**
     * Soft-deleted orders stay readable: an entry keeps its order after the order is deleted.
     *
     * @return BelongsTo<Order, $this>
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class)->withTrashed();
    }

    /**
     * Soft-deleted products stay readable too.
     *
     * @return BelongsTo<Product, $this>
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class)->withTrashed();
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}

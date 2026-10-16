<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\OrderProgress;
use App\Models\Concerns\BelongsToWorkshop;
use Carbon\CarbonInterface;
use Database\Factories\OrderLineFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One product of an order with its whole-unit quantity. One line per product per order (database-unique).
 *
 * @property int $id
 * @property int $workshop_id The workshop of the order (and of its product). Never mass-assignable: it comes from the order.
 * @property int $order_id
 * @property int $product_id
 * @property int $quantity Whole units, 1 to 1 000 000.
 * @property int $position Display order: the order the lines were entered in.
 * @property int $in_cutting Units in cutting (S-49). To start is derived: see `to_start`.
 * @property int $ready_to_ship Units ready to ship.
 * @property int $shipped Units shipped. The three counts never add up to more than the quantity.
 * @property-read int $to_start The quantity minus the three counts: whatever has not moved yet.
 * @property-read int $started The three counts added up: the units that have left To start.
 * @property CarbonInterface $created_at
 * @property CarbonInterface $updated_at
 */
#[Fillable(['product_id', 'quantity', 'position'])]
class OrderLine extends Model
{
    /** @use HasFactory<OrderLineFactory> */
    use BelongsToWorkshop, HasFactory;

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'position' => 'integer',
            'in_cutting' => 'integer',
            'ready_to_ship' => 'integer',
            'shipped' => 'integer',
        ];
    }

    /**
     * To start is never stored, so the four figures of a line can't stop adding up to its quantity.
     *
     * @return Attribute<int, never>
     */
    protected function toStart(): Attribute
    {
        return Attribute::get(fn (): int => $this->quantity - $this->started);
    }

    /**
     * The units that have left To start.
     *
     * @return Attribute<int, never>
     */
    protected function started(): Attribute
    {
        return Attribute::get(fn (): int => $this->in_cutting + $this->ready_to_ship + $this->shipped);
    }

    /**
     * "Not started" while every unit is still to start, "Shipped" once every unit has shipped, "In progress" in between.
     */
    public function progress(): OrderProgress
    {
        return match (true) {
            $this->started === 0 => OrderProgress::NotStarted,
            $this->shipped === $this->quantity => OrderProgress::Shipped,
            default => OrderProgress::InProgress,
        };
    }

    /** @return BelongsTo<Order, $this> */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * Soft-deleted products stay readable: a line keeps its product after the product is deleted.
     *
     * @return BelongsTo<Product, $this>
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class)->withTrashed();
    }
}

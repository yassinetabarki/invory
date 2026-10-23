<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\OrderProgress;
use App\Models\Concerns\BelongsToWorkshop;
use App\Models\Concerns\HasStageCounts;
use Carbon\CarbonInterface;
use Database\Factories\OrderLineFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * One product of an order with its whole-unit quantity. One line per product per order (database-unique).
 *
 * @property int $id
 * @property int $workshop_id The workshop of the order (and of its product). Never mass-assignable: it comes from the order.
 * @property int $order_id
 * @property int $product_id
 * @property int $quantity Whole units, 1 to 1 000 000: the sum of the line's sizes.
 * @property int $position Display order: the order the lines were entered in.
 * @property int|null $size_list_id The size list the line counts by (ADR-0016). Never null in PostgreSQL; never mass-assignable: the Actions set it.
 * @property int $in_cutting Units in cutting (S-49): the sum of the line's sizes. To start is derived: see `to_start`.
 * @property int $ready_to_ship Units ready to ship.
 * @property int $shipped Units shipped. The three counts never add up to more than the quantity.
 * @property CarbonInterface $created_at
 * @property CarbonInterface $updated_at
 */
#[Fillable(['product_id', 'quantity', 'position'])]
class OrderLine extends Model
{
    /** @use HasFactory<OrderLineFactory> */
    use BelongsToWorkshop, HasFactory, HasStageCounts;

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
     * The cuts of this line (S-53). Latest first is the caller's order.
     *
     * @return HasMany<OrderLineCut, $this>
     */
    public function cuts(): HasMany
    {
        return $this->hasMany(OrderLineCut::class);
    }

    /**
     * The sizes of this line, in the size list's order. The order is a subselect on the size's position, not a join: the rows keep their
     * columns (strict models), it runs the same on SQLite and PostgreSQL, and it survives eager loading.
     *
     * @return HasMany<OrderLineSize, $this>
     */
    public function sizes(): HasMany
    {
        return $this->hasMany(OrderLineSize::class)
            ->orderBy(Size::query()->select('position')->whereColumn('sizes.id', 'order_line_sizes.size_id'));
    }

    /** @return BelongsTo<SizeList, $this> */
    public function sizeList(): BelongsTo
    {
        return $this->belongsTo(SizeList::class);
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

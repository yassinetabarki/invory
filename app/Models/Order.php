<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\OrderProgress;
use App\Models\Concerns\BelongsToWorkshop;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Database\Factories\OrderFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * A client's order: who ordered, the day, an optional note, and its product lines (S-48). Recording, editing or deleting one never
 * changes material stock, sales or any figure. Deleting is a soft delete: the lines stay with the order.
 *
 * @property int $id
 * @property int $workshop_id Never mass-assignable: Actions set it from the actor.
 * @property string $client
 * @property CarbonImmutable $ordered_on The day (a date, no time).
 * @property string|null $note
 * @property int|null $user_id Who recorded it. Never mass-assignable: it comes from the actor.
 * @property CarbonInterface $created_at
 * @property CarbonInterface $updated_at
 * @property int|string|null $lines_sum_quantity Not a column: `withSum('lines', 'quantity')` provides it (null without lines; PostgreSQL returns the sum as a string). Strict models throw when a caller forgot.
 * @property CarbonInterface|null $deleted_at
 */
#[Fillable(['client', 'ordered_on', 'note'])]
class Order extends Model
{
    /** @use HasFactory<OrderFactory> */
    use BelongsToWorkshop, HasFactory, SoftDeletes;

    /**
     * `date:Y-m-d` stores the plain day, as on sales.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'ordered_on' => 'date:Y-m-d',
        ];
    }

    /**
     * The lines in the order they were entered.
     *
     * @return HasMany<OrderLine, $this>
     */
    public function lines(): HasMany
    {
        return $this->hasMany(OrderLine::class)->orderBy('position')->orderBy('id');
    }

    /** Units in cutting, summed over the loaded lines (no query: the order must carry its `lines`). */
    public function unitsInCutting(): int
    {
        return (int) $this->lines->sum('in_cutting');
    }

    /** Units still to start, summed over the loaded lines. */
    public function unitsToStart(): int
    {
        return (int) $this->lines->sum(fn (OrderLine $line): int => $line->to_start);
    }

    /** Units ready to ship, summed over the loaded lines. */
    public function unitsReadyToShip(): int
    {
        return (int) $this->lines->sum('ready_to_ship');
    }

    /** Units shipped, summed over the loaded lines. */
    public function unitsShipped(): int
    {
        return (int) $this->lines->sum('shipped');
    }

    /**
     * Units that hold material taken out of stock and haven't left yet: in cutting plus ready to ship. While there are any, the order
     * can't be deleted.
     */
    public function unitsHeld(): int
    {
        return $this->unitsInCutting() + $this->unitsReadyToShip();
    }

    /**
     * "Not started" while no unit of any line has left To start, "Shipped" once every line is fully shipped, "In progress" otherwise.
     * Read from the loaded lines.
     */
    public function progress(): OrderProgress
    {
        return match (true) {
            $this->lines->every(fn (OrderLine $line): bool => $line->started === 0) => OrderProgress::NotStarted,
            $this->lines->every(fn (OrderLine $line): bool => $line->progress() === OrderProgress::Shipped) => OrderProgress::Shipped,
            default => OrderProgress::InProgress,
        };
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Orders whose client or note contains the term, or that have a line of a product whose name or SKU does (case-insensitive;
     * soft-deleted products count, a line keeps them). The lines are matched by an id subquery, so an order is never listed twice.
     * No filter for a null or empty term.
     *
     * @param  Builder<Order>  $query
     */
    #[Scope]
    protected function search(Builder $query, ?string $term): void
    {
        if ($term === null || $term === '') {
            return;
        }

        $query->where(fn (Builder $match) => $match
            ->whereLike('client', "%{$term}%")
            ->orWhereLike('note', "%{$term}%")
            ->orWhereIn('id', OrderLine::query()
                ->select('order_id')
                ->whereIn('product_id', Product::query()->withTrashed()->search($term)->select('id'))));
    }

    /**
     * Newest order date first; on the same date the order recorded last.
     *
     * @param  Builder<Order>  $query
     */
    #[Scope]
    protected function newestFirst(Builder $query): void
    {
        $query->orderByDesc('ordered_on')->orderByDesc('id');
    }

    /**
     * Open orders: at least one line with units still to ship. The one definition behind the Status filter and the totals (S-50);
     * it agrees with `progress()` for every order that has lines.
     *
     * @param  Builder<Order>  $query
     */
    #[Scope]
    protected function open(Builder $query): void
    {
        $query->whereHas('lines', fn (Builder $line) => $line->whereColumn('order_lines.shipped', '<', 'order_lines.quantity'));
    }

    /**
     * Shipped orders: every line fully shipped, the opposite of {@see self::open()}.
     *
     * @param  Builder<Order>  $query
     */
    #[Scope]
    protected function shipped(Builder $query): void
    {
        $query->whereDoesntHave('lines', fn (Builder $line) => $line->whereColumn('order_lines.shipped', '<', 'order_lines.quantity'));
    }
}

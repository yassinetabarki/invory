<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Database\Factories\SaleFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One recorded sale: a product, whole units, the price of one unit and the day. It never changes material stock (decision Q8),
 * and its total is never stored: App\Domain\Sales\SalesCalculator computes it.
 *
 * @property int $id
 * @property int $product_id
 * @property int $quantity Whole units, 1 to 1 000 000.
 * @property string $unit_price Decimal string, scale 4.
 * @property CarbonImmutable $sold_on The day (a date, no time).
 * @property string|null $note
 * @property int|null $user_id Who recorded it. Never mass-assignable: it comes from the actor.
 * @property CarbonInterface $created_at
 * @property CarbonInterface $updated_at
 */
#[Fillable(['product_id', 'quantity', 'unit_price', 'sold_on', 'note'])]
class Sale extends Model
{
    /** @use HasFactory<SaleFactory> */
    use HasFactory;

    /**
     * `date:Y-m-d` stores the plain day (a bare `date` cast writes "Y-m-d H:i:s", which SQLite keeps as text and then
     * sorts after the same day's "Y-m-d" bound).
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'unit_price' => 'decimal:4',
            'sold_on' => 'date:Y-m-d',
        ];
    }

    /**
     * The product, soft-deleted ones included: the sale stays in the log and the totals after its product is deleted (D-3).
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

    /**
     * Sales made on an inclusive range of days. Written as `>= first` and `< the day after last`, so it also holds for a value
     * stored with a time part.
     *
     * @param  Builder<Sale>  $query
     */
    #[Scope]
    protected function soldBetween(Builder $query, CarbonInterface $first, CarbonInterface $last): void
    {
        $query->where('sold_on', '>=', $first->toDateString())
            ->where('sold_on', '<', $last->addDay()->toDateString());
    }

    /**
     * Newest first: the day, then the id (the order of the sales log).
     *
     * @param  Builder<Sale>  $query
     */
    #[Scope]
    protected function newestFirst(Builder $query): void
    {
        $query->orderByDesc('sold_on')->orderByDesc('id');
    }
}

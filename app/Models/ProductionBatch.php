<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ProductionBatchStatus;
use App\Models\Concerns\BelongsToWorkshop;
use Carbon\CarbonInterface;
use Database\Factories\ProductionBatchFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A production batch (INV-5, ADR-0023): units of one product in one colour and one size, made together for the shelf, numbered per
 * workshop ("B-0147"). While in production it is In cutting (`phase_id` null) or in one of the workshop's phases, since `phase_since`.
 * Put in stock (all its units) or Undo batch ends it; it is never deleted.
 *
 * @property int $id
 * @property int $workshop_id Never mass-assignable.
 * @property int $number At least 1, once per workshop, never reused. Shown as {@see self::$label}.
 * @property int $product_id
 * @property int $colour_id
 * @property int $size_id
 * @property int $production_cut_id The Start batch whose materials it holds.
 * @property int|null $split_from_id The batch it was split from.
 * @property int $units What it holds now, 1 to 1,000,000.
 * @property ProductionBatchStatus $status
 * @property int|null $phase_id Null = In cutting while in production; null again once it left Progress.
 * @property CarbonInterface $phase_since The day it got to its phase.
 * @property CarbonInterface $started_on The day its Start batch ran.
 * @property int|null $user_id Who started it. Never mass-assignable.
 * @property int|null $product_restock_id The restock its last Put in stock wrote.
 * @property CarbonInterface|null $finished_at
 * @property CarbonInterface|null $undone_at
 * @property int|null $undone_by
 * @property-read string $label "B-0147": the number with at least four digits.
 * @property CarbonInterface $created_at
 * @property CarbonInterface $updated_at
 */
#[Fillable(['number', 'product_id', 'colour_id', 'size_id', 'production_cut_id', 'split_from_id', 'units', 'status', 'phase_id', 'phase_since', 'started_on'])]
class ProductionBatch extends Model
{
    /** @use HasFactory<ProductionBatchFactory> */
    use BelongsToWorkshop, HasFactory;

    public const MAX_UNITS = 1000000;

    /** "B-0147": the number padded to four digits; B-10000 and on keep all of theirs. */
    public static function labelOf(int $number): string
    {
        return 'B-'.str_pad((string) $number, 4, '0', STR_PAD_LEFT);
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'number' => 'integer',
            'units' => 'integer',
            'status' => ProductionBatchStatus::class,
            'phase_since' => 'date',
            'started_on' => 'date',
            'finished_at' => 'datetime',
            'undone_at' => 'datetime',
        ];
    }

    /** @return Attribute<string, never> */
    protected function label(): Attribute
    {
        return Attribute::get(fn (): string => self::labelOf($this->number));
    }

    public function isInProduction(): bool
    {
        return $this->status === ProductionBatchStatus::InProduction;
    }

    public function isInCutting(): bool
    {
        return $this->isInProduction() && $this->phase_id === null;
    }

    /**
     * Deleted products stay readable: a batch shows "Denim Jacket (deleted)".
     *
     * @return BelongsTo<Product, $this>
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class)->withTrashed();
    }

    /** @return BelongsTo<Colour, $this> */
    public function colour(): BelongsTo
    {
        return $this->belongsTo(Colour::class);
    }

    /** @return BelongsTo<Size, $this> */
    public function size(): BelongsTo
    {
        return $this->belongsTo(Size::class);
    }

    /** @return BelongsTo<ProductionPhase, $this> */
    public function phase(): BelongsTo
    {
        return $this->belongsTo(ProductionPhase::class, 'phase_id');
    }

    /** @return BelongsTo<ProductionCut, $this> */
    public function cut(): BelongsTo
    {
        return $this->belongsTo(ProductionCut::class, 'production_cut_id');
    }

    /** @return BelongsTo<self, $this> */
    public function splitFrom(): BelongsTo
    {
        return $this->belongsTo(self::class, 'split_from_id');
    }

    /** @return HasMany<ProductionBatchMove, $this> */
    public function moves(): HasMany
    {
        return $this->hasMany(ProductionBatchMove::class);
    }

    /**
     * The batches on Progress.
     *
     * @param  Builder<self>  $query
     */
    #[Scope]
    protected function inProduction(Builder $query): void
    {
        $query->where('status', ProductionBatchStatus::InProduction->value);
    }
}

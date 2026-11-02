<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\BelongsToWorkshop;
use Carbon\CarbonInterface;
use Database\Factories\ProductRestockFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * One restock of finished products (INV-3, ADR-0022): units that were made or arrived, added to the shelf in their colours and sizes,
 * for one or several products. Its {@see ProductRestockLine}s say what it added; the shelf count itself is `product_size_stocks`.
 * A restock is never edited or deleted: Undo takes its units back off the shelf and sets `undone_at` and `undone_by`.
 *
 * @property int $id
 * @property int $workshop_id Never mass-assignable: it comes from the current workshop or the Action.
 * @property CarbonInterface $restocked_on The day the units were made or arrived, never after today.
 * @property string|null $reference Up to 64 characters: a delivery note, a batch, who made them.
 * @property string|null $note Up to 255 characters.
 * @property int $units The total it added, at least 1. Written once.
 * @property int|null $user_id Who recorded it; null once that account is deleted. Never mass-assignable.
 * @property CarbonInterface|null $undone_at
 * @property int|null $undone_by Who undid it; null while it isn't undone, or once that account is deleted.
 * @property CarbonInterface $created_at When it was recorded.
 * @property CarbonInterface $updated_at
 */
#[Fillable(['restocked_on', 'reference', 'note', 'units'])]
class ProductRestock extends Model
{
    /** @use HasFactory<ProductRestockFactory> */
    use BelongsToWorkshop, HasFactory;

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['restocked_on' => 'date', 'units' => 'integer', 'undone_at' => 'datetime'];
    }

    /** @return HasMany<ProductRestockLine, $this> */
    public function lines(): HasMany
    {
        return $this->hasMany(ProductRestockLine::class);
    }

    /** @return BelongsTo<User, $this> */
    public function recorder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /** @return BelongsTo<User, $this> */
    public function undoer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'undone_by');
    }

    public function isUndone(): bool
    {
        return $this->undone_at !== null;
    }

    /**
     * The restocks that still count: not undone.
     *
     * @param  Builder<self>  $query
     */
    #[Scope]
    protected function active(Builder $query): void
    {
        $query->whereNull('undone_at');
    }

    /**
     * Newest first: by date, then the latest recorded.
     *
     * @param  Builder<self>  $query
     */
    #[Scope]
    protected function newestFirst(Builder $query): void
    {
        $query->orderByDesc('restocked_on')->orderByDesc('id');
    }
}

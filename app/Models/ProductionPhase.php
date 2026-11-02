<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\BelongsToWorkshop;
use Carbon\CarbonInterface;
use Database\Factories\ProductionPhaseFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * One of the workshop's own production phases (INV-5, ADR-0023), between In cutting and Put in stock, which have no row. A workshop
 * has 0 to 6, at positions 1…n with no gap; the Actions keep that under the workshop's row lock.
 *
 * @property int $id
 * @property int $workshop_id Never mass-assignable.
 * @property string $name Up to 24 characters, once per workshop ignoring capitals.
 * @property int $position 1 to 6.
 * @property CarbonInterface $created_at
 * @property CarbonInterface $updated_at
 */
#[Fillable(['name', 'position'])]
class ProductionPhase extends Model
{
    /** @use HasFactory<ProductionPhaseFactory> */
    use BelongsToWorkshop, HasFactory;

    public const MAX = 6;

    public const NAME_MAX = 24;

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['position' => 'integer'];
    }

    /** @return HasMany<ProductionBatch, $this> */
    public function batches(): HasMany
    {
        return $this->hasMany(ProductionBatch::class, 'phase_id');
    }

    /**
     * In path order.
     *
     * @param  Builder<self>  $query
     */
    #[Scope]
    protected function inOrder(Builder $query): void
    {
        $query->orderBy('position')->orderBy('id');
    }
}

<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\BelongsToWorkshop;
use Carbon\CarbonInterface;
use Database\Factories\ColourFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use LogicException;

/**
 * One colour of a workshop's list (INV-1, ADR-0018). Never removed. Its name, swatch and position can change; its `code` never does
 * (INV-2 builds variant SKUs from it), so saving a changed code on an existing colour throws, whoever calls.
 *
 * @property int $id
 * @property int $workshop_id Never mass-assignable: it comes from the current workshop or the Action.
 * @property string $code 2 to 4 of A-Z 0-9, unique in the workshop.
 * @property string $name At most 32 characters, unique in the workshop ignoring capitals.
 * @property string $swatch '#RRGGBB', capitals.
 * @property int $position
 * @property CarbonInterface $created_at
 * @property CarbonInterface $updated_at
 */
#[Fillable(['code', 'name', 'swatch', 'position'])]
class Colour extends Model
{
    /** @use HasFactory<ColourFactory> */
    use BelongsToWorkshop, HasFactory;

    protected static function booted(): void
    {
        static::updating(static function (Colour $colour): void {
            if ($colour->isDirty('code')) {
                throw new LogicException('The code of a colour never changes (ADR-0018).');
            }
        });
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['position' => 'integer'];
    }

    /**
     * The non-deleted products that have this colour (the soft-delete scope leaves deleted ones out of the "Used by" count).
     *
     * @return BelongsToMany<Product, $this>
     */
    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'product_colours');
    }

    /**
     * The list order: position, then name, then id (the id keeps it stable).
     *
     * @param  Builder<Colour>  $query
     */
    #[Scope]
    protected function ordered(Builder $query): void
    {
        $query->orderBy('position')->orderBy('name')->orderBy('id');
    }
}

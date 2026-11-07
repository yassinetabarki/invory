<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\BelongsToWorkshop;
use Carbon\CarbonInterface;
use Database\Factories\SizeFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * One size of a workshop's own list (INV-18, ADR-0028, table `sizes`): "S", "36", "One size". Records refer to a size by its id, so a
 * rename changes the words shown and nothing else. The list order is position, then id ({@see self::ordered()}).
 *
 * "One size" is the flag `is_one_size`, never the name: at most one row per workshop has it.
 *
 * @property int $id
 * @property int $workshop_id Never mass-assignable: it comes from the current workshop or the Action.
 * @property string $label At most 16 characters (12 for a new one), unique in the workshop ignoring capitals.
 * @property int $position
 * @property bool $is_one_size
 * @property CarbonInterface $created_at
 * @property CarbonInterface $updated_at
 */
#[Fillable(['label', 'position', 'is_one_size'])]
class Size extends Model
{
    /** @use HasFactory<SizeFactory> */
    use BelongsToWorkshop, HasFactory;

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['position' => 'integer', 'is_one_size' => 'boolean'];
    }

    /**
     * The list order: position, then id (the id keeps ties in creation order, so S, M, L never read L, M, S).
     *
     * @param  Builder<Size>  $query
     */
    #[Scope]
    protected function ordered(Builder $query): void
    {
        $query->orderBy($query->qualifyColumn('position'))->orderBy($query->qualifyColumn('id'));
    }
}

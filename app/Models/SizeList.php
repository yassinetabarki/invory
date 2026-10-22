<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A fixed list of sizes (ADR-0015, table `size_lists`). Installation-wide reference data, filled by a migration, edited by no screen.
 * `code` is immutable; `name` is the text the screens show ("S, M, L, XL", "36 to 46", "One size").
 *
 * @property int $id
 * @property string $code
 * @property string $name
 * @property CarbonInterface $created_at
 * @property CarbonInterface $updated_at
 */
#[Fillable(['code', 'name'])]
class SizeList extends Model
{
    public const S_TO_XL = 's_to_xl';

    public const EU_36_TO_46 = 'eu_36_to_46';

    public const ONE_SIZE = 'one_size';

    /**
     * The sizes of the list, in order.
     *
     * @return HasMany<Size, $this>
     */
    public function sizes(): HasMany
    {
        return $this->hasMany(Size::class)->orderBy('position');
    }
}

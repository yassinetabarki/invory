<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One size of a {@see SizeList} (ADR-0015, table `sizes`): "S", "36", "One size". Later stories refer to a size by its id.
 *
 * @property int $id
 * @property int $size_list_id
 * @property string $label
 * @property int $position
 * @property CarbonInterface $created_at
 * @property CarbonInterface $updated_at
 */
#[Fillable(['size_list_id', 'label', 'position'])]
class Size extends Model
{
    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['position' => 'integer'];
    }

    /** @return BelongsTo<SizeList, $this> */
    public function sizeList(): BelongsTo
    {
        return $this->belongsTo(SizeList::class);
    }
}

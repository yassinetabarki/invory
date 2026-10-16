<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\BelongsToWorkshop;
use Carbon\CarbonInterface;
use Database\Factories\OrderLineCutFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * One "Start cutting" of an order line (S-53, ADR-0014): how many units, and how many of them were moved back to To start so far.
 * Its materials are remembered in {@see OrderLineCutMaterial}; they are the only source of a return.
 *
 * @property int $id
 * @property int $workshop_id The workshop of the line. Never mass-assignable: it comes from the line.
 * @property int $order_line_id
 * @property int $units Whole units, at least 1.
 * @property int $units_returned Units moved back so far, never above `units`.
 * @property int|null $user_id Who started the cut (null once that user is deleted). Never mass-assignable: it comes from the actor.
 * @property CarbonInterface $created_at
 * @property CarbonInterface $updated_at
 */
#[Fillable(['units', 'units_returned'])]
class OrderLineCut extends Model
{
    /** @use HasFactory<OrderLineCutFactory> */
    use BelongsToWorkshop, HasFactory;

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'units' => 'integer',
            'units_returned' => 'integer',
        ];
    }

    /** @return BelongsTo<OrderLine, $this> */
    public function line(): BelongsTo
    {
        return $this->belongsTo(OrderLine::class, 'order_line_id');
    }

    /** @return HasMany<OrderLineCutMaterial, $this> */
    public function materials(): HasMany
    {
        return $this->hasMany(OrderLineCutMaterial::class);
    }
}

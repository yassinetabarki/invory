<?php

declare(strict_types=1);

namespace App\Models\Concerns;

use App\Exceptions\MissingWorkshopException;
use App\Models\Scopes\WorkshopScope;
use App\Models\Workshop;
use App\Services\Workshops\CurrentWorkshop;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Marks a model as owned by a workshop (ADR-0013): every query is limited to the current workshop by {@see WorkshopScope}, and a
 * record created without a `workshop_id` takes the current workshop, and is refused when there is none. There is deliberately no
 * way to switch the scope off in the app: `tests/Arch/WorkshopScopeTest.php` fails on `withoutGlobalScope(s)`.
 *
 * `workshop_id` is never mass-assignable: Actions set it from the actor or the parent record, and a child takes its parent's.
 */
trait BelongsToWorkshop
{
    public static function bootBelongsToWorkshop(): void
    {
        static::addGlobalScope(new WorkshopScope);

        static::creating(static function (Model $model): void {
            if ($model->getAttribute('workshop_id') !== null) {
                return;
            }

            $model->setAttribute('workshop_id', CurrentWorkshop::id() ?? throw MissingWorkshopException::for($model::class));
        });
    }

    /** @return BelongsTo<Workshop, $this> */
    public function workshop(): BelongsTo
    {
        return $this->belongsTo(Workshop::class);
    }
}

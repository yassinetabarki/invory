<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\CarbonInterface;
use Database\Factories\WorkshopFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A workshop: the business account (ADR-0013). Every user belongs to exactly one, and S-44-b onwards everything they record does too.
 * It has no attributes yet (S-46 adds its name), so nothing is mass-assignable.
 *
 * @property int $id
 * @property CarbonInterface $created_at
 * @property CarbonInterface $updated_at
 */
#[Fillable([])]
class Workshop extends Model
{
    /** @use HasFactory<WorkshopFactory> */
    use HasFactory;

    /** @return HasMany<User, $this> */
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }
}

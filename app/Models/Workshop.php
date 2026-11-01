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
 * @property int $last_barcode_serial The last in-store barcode serial given (INV-8, ADR-0020); read and saved under the workshop's row lock.
 * @property CarbonInterface $created_at
 * @property CarbonInterface $updated_at
 */
#[Fillable([])]
class Workshop extends Model
{
    /** @use HasFactory<WorkshopFactory> */
    use HasFactory;

    /**
     * The database default, so a freshly created workshop reads like a stored one without a refresh.
     *
     * @var array<string, mixed>
     */
    protected $attributes = ['last_barcode_serial' => 0];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['last_barcode_serial' => 'integer'];
    }

    /** @return HasMany<User, $this> */
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }
}

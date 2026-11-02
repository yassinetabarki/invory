<?php

declare(strict_types=1);

namespace App\Models;

use App\Domain\Clients\PhoneNumber;
use App\Enums\ClientSource;
use App\Models\Concerns\BelongsToWorkshop;
use Carbon\CarbonInterface;
use Database\Factories\ClientFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use InvalidArgumentException;

/**
 * A client of the workshop (INV-4, ADR-0021): a person or a shop, with a name, a phone, a city and, if known, where she found the
 * workshop. A client is never edited or removed, and deleting an order never removes its client.
 *
 * Setting `phone` sets `phone_key` too, by {@see PhoneNumber}, whoever writes (Actions, factories): the key is what makes a phone
 * unique in the workshop, however it was typed. A phone Invory doesn't know is refused with an exception; requests check it first.
 *
 * @property int $id
 * @property int $workshop_id Never mass-assignable: it comes from the current workshop or the Action.
 * @property string $name Up to 100 characters; two clients may share one.
 * @property string $phone As the screens show it: "22 345 678".
 * @property string $phone_key Digits with the country code: "21622345678". Unique in the workshop.
 * @property string $city Up to 60 characters.
 * @property ClientSource|null $source
 * @property string|null $source_other Only with the source Other, up to 40 characters.
 * @property int|null $user_id Who added her. Never mass-assignable.
 * @property CarbonInterface $created_at
 * @property CarbonInterface $updated_at
 */
#[Fillable(['name', 'phone', 'city', 'source', 'source_other'])]
class Client extends Model
{
    /** @use HasFactory<ClientFactory> */
    use BelongsToWorkshop, HasFactory;

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['source' => ClientSource::class];
    }

    /** @return Attribute<string, string> */
    protected function phone(): Attribute
    {
        return Attribute::make(
            set: function (string $value): array {
                $phone = PhoneNumber::parse($value) ?? throw new InvalidArgumentException("Not a phone number Invory knows: {$value}");

                return ['phone' => $phone->display, 'phone_key' => $phone->key];
            },
        );
    }

    /**
     * Her orders (the soft-deleted ones are left out by the scope).
     *
     * @return HasMany<Order, $this>
     */
    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Clients whose name or city contains the term (case-insensitive), or whose phone contains its digits ("22 345" finds 22 345 678).
     * No filter for a null or empty term.
     *
     * @param  Builder<Client>  $query
     */
    #[Scope]
    protected function search(Builder $query, ?string $term): void
    {
        $term = trim((string) $term);

        if ($term === '') {
            return;
        }

        $digits = PhoneNumber::searchDigits($term);

        $query->where(fn (Builder $q) => $q
            ->whereLike('name', "%{$term}%")
            ->orWhereLike('city', "%{$term}%")
            ->when($digits !== null, fn (Builder $phone) => $phone->orWhereLike('phone_key', "%{$digits}%")));
    }

    /**
     * Clients who came from this source (no filter for null).
     *
     * @param  Builder<Client>  $query
     */
    #[Scope]
    protected function fromSource(Builder $query, ?ClientSource $source): void
    {
        $query->when($source !== null, fn (Builder $q) => $q->where('source', $source?->value));
    }

    /**
     * A → Z by name, then the oldest first: the order of every client list.
     *
     * @param  Builder<Client>  $query
     */
    #[Scope]
    protected function alphabetical(Builder $query): void
    {
        $query->orderBy('name')->orderBy('id');
    }
}

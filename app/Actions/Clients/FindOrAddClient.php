<?php

declare(strict_types=1);

namespace App\Actions\Clients;

use App\Domain\Clients\PhoneNumber;
use App\Enums\ClientSource;
use App\Models\Client;
use App\Models\User;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

final readonly class FindOrAddClient
{
    /**
     * The client of the workshop with this phone (compared on its key), or a new client made from the fields (INV-4, ADR-0021). A
     * client found keeps her saved details: an order never changes them. Called inside the order's transaction: the insert runs in a
     * savepoint, so a client added meanwhile by someone else (the unique key refuses the second row) is found instead.
     *
     * @param  array<string, mixed>  $fields  validated: `phone`, and `name`, `city`, `source`, `source_other` for a new client
     * @return array{Client, bool} the client, and whether she was added now
     */
    public function handle(array $fields, User $actor): array
    {
        $phone = PhoneNumber::parse(self::text($fields, 'phone')) ?? throw new InvalidArgumentException('The phone was not validated.');
        $found = $this->byKey($phone->key);

        if ($found !== null) {
            return [$found, false];
        }

        try {
            return [DB::transaction(fn (): Client => self::add($fields, $actor)), true];
        } catch (UniqueConstraintViolationException $e) {
            return [$this->byKey($phone->key) ?? throw $e, false];
        }
    }

    /**
     * A new client of the actor's workshop. Other source is kept only with the source Other. Shared with `AddClient`.
     *
     * @param  array<string, mixed>  $fields  validated: `phone`, `name`, `city`, and `source`, `source_other` when given
     */
    public static function add(array $fields, User $actor): Client
    {
        $source = ClientSource::tryFrom(self::text($fields, 'source'));
        $other = self::text($fields, 'source_other');

        $client = new Client([
            'name' => self::text($fields, 'name'),
            'phone' => self::text($fields, 'phone'),
            'city' => self::text($fields, 'city'),
            'source' => $source,
            'source_other' => $source === ClientSource::Other && $other !== '' ? $other : null,
        ]);

        // Never mass-assignable: the workshop and who added her come from the actor, not from the request.
        $client->workshop_id = $actor->workshop_id;
        $client->user_id = $actor->id;
        $client->save();

        return $client;
    }

    /**
     * A validated text field, trimmed; '' when it was left out or empty.
     *
     * @param  array<string, mixed>  $fields
     */
    private static function text(array $fields, string $key): string
    {
        $value = $fields[$key] ?? null;

        return is_string($value) ? trim($value) : '';
    }

    private function byKey(string $key): ?Client
    {
        return Client::query()->where('phone_key', $key)->first();
    }
}

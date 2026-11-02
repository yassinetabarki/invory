<?php

declare(strict_types=1);

namespace App\Actions\Clients;

use App\Models\Client;
use App\Models\User;
use Illuminate\Support\Facades\DB;

final readonly class AddClient
{
    /**
     * Adds a client from the Add client window (INV-4, AC-3). The request has already refused a phone another client has; the
     * unique key refuses one added meanwhile.
     *
     * @param  array<string, mixed>  $fields  validated: `phone`, `name`, `city`, and `source`, `source_other` when given
     */
    public function handle(array $fields, User $actor): Client
    {
        return DB::transaction(fn (): Client => FindOrAddClient::add($fields, $actor));
    }
}

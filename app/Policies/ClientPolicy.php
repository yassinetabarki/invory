<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Client;
use App\Models\User;
use Illuminate\Auth\Access\Response;

/**
 * Any member of a workshop sees and adds that workshop's clients (ADR-0013, INV-4). A client of another workshop answers 404, never
 * 403: through HTTP the workshop scope already hides her, this is the second layer. A client is never edited or removed, so there is
 * no `update` or `delete`.
 */
class ClientPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function view(User $user, Client $client): Response
    {
        return $user->workshop_id === $client->workshop_id ? Response::allow() : Response::denyAsNotFound();
    }
}

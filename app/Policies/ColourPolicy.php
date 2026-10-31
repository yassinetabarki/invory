<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Colour;
use App\Models\User;
use Illuminate\Auth\Access\Response;

/**
 * Any member of a workshop manages that workshop's colours, like its categories (ADR-0013, decision Q3). A colour of another workshop
 * answers 404, never 403: through HTTP the workshop scope already hides it, this is the second layer. A colour is never deleted.
 */
class ColourPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Colour $colour): Response
    {
        return $user->workshop_id === $colour->workshop_id ? Response::allow() : Response::denyAsNotFound();
    }
}

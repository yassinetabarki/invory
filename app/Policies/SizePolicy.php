<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Size;
use App\Models\User;
use Illuminate\Auth\Access\Response;

/**
 * Any member of a workshop manages that workshop's sizes, like its colours (INV-18, ADR-0028). A size of another workshop answers 404,
 * never 403: through HTTP the workshop scope already hides it, this is the second layer. A size is never deleted.
 */
class SizePolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Size $size): Response
    {
        return $user->workshop_id === $size->workshop_id ? Response::allow() : Response::denyAsNotFound();
    }
}

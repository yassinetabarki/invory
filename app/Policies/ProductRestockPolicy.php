<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\ProductRestock;
use App\Models\User;
use Illuminate\Auth\Access\Response;

/**
 * Any member of a workshop sees, records and undoes that workshop's restocks (ADR-0013, INV-3). A restock of another workshop answers
 * 404, never 403: through HTTP the workshop scope already hides it, this is the second layer. A restock is never edited or deleted:
 * `update` is Undo.
 */
class ProductRestockPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function view(User $user, ProductRestock $restock): Response
    {
        return $this->sameWorkshop($user, $restock);
    }

    public function update(User $user, ProductRestock $restock): Response
    {
        return $this->sameWorkshop($user, $restock);
    }

    private function sameWorkshop(User $user, ProductRestock $restock): Response
    {
        return $user->workshop_id === $restock->workshop_id ? Response::allow() : Response::denyAsNotFound();
    }
}

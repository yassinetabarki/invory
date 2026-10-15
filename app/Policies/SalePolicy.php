<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Sale;
use App\Models\User;
use Illuminate\Auth\Access\Response;

/**
 * Any member of a workshop edits that workshop's sales (ADR-0013, decision Q3). A sale of another workshop answers 404, never 403:
 * through HTTP the workshop scope already hides it, this is the second layer.
 */
class SalePolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Sale $sale): Response
    {
        return $this->sameWorkshop($user, $sale);
    }

    public function delete(User $user, Sale $sale): Response
    {
        return $this->sameWorkshop($user, $sale);
    }

    private function sameWorkshop(User $user, Sale $sale): Response
    {
        return $user->workshop_id === $sale->workshop_id ? Response::allow() : Response::denyAsNotFound();
    }
}

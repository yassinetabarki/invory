<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Product;
use App\Models\User;
use Illuminate\Auth\Access\Response;

/**
 * Any member of a workshop edits that workshop's catalogue (ADR-0013, decision Q3). A product of another workshop answers 404, never
 * 403: through HTTP the workshop scope already hides it, this is the second layer.
 */
class ProductPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Product $product): Response
    {
        return $this->sameWorkshop($user, $product);
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Product $product): Response
    {
        return $this->sameWorkshop($user, $product);
    }

    public function delete(User $user, Product $product): Response
    {
        return $this->sameWorkshop($user, $product);
    }

    private function sameWorkshop(User $user, Product $product): Response
    {
        return $user->workshop_id === $product->workshop_id ? Response::allow() : Response::denyAsNotFound();
    }
}

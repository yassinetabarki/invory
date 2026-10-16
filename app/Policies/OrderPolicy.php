<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Order;
use App\Models\User;
use Illuminate\Auth\Access\Response;

/**
 * Any member of a workshop edits that workshop's orders (ADR-0013). An order of another workshop answers 404, never 403: through
 * HTTP the workshop scope already hides it, this is the second layer.
 */
class OrderPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function view(User $user, Order $order): Response
    {
        return $this->sameWorkshop($user, $order);
    }

    public function update(User $user, Order $order): Response
    {
        return $this->sameWorkshop($user, $order);
    }

    public function delete(User $user, Order $order): Response
    {
        return $this->sameWorkshop($user, $order);
    }

    private function sameWorkshop(User $user, Order $order): Response
    {
        return $user->workshop_id === $order->workshop_id ? Response::allow() : Response::denyAsNotFound();
    }
}

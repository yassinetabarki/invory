<?php

declare(strict_types=1);

namespace App\Services\Workshops;

use App\Models\User;
use App\Models\Workshop;
use Closure;

/**
 * The workshop the code is working for (ADR-0013): the one set explicitly with {@see self::run()}, otherwise the signed-in user's,
 * otherwise none (migrations, seeders, console commands).
 *
 * Callers use the static methods; they delegate to one scoped instance (registered in AppServiceProvider), so the explicit
 * workshop never outlives its request, queue job or test.
 */
final class CurrentWorkshop
{
    private ?int $explicit = null;

    /** The id of the current workshop, or null when there is none. The signed-in user is read lazily, on the first call. */
    public static function id(): ?int
    {
        return app(self::class)->resolve();
    }

    /**
     * Run the callback as this workshop. The previous one is restored afterwards, also when the callback throws.
     *
     * @template TReturn
     *
     * @param  Closure(): TReturn  $callback
     * @return TReturn
     */
    public static function run(Workshop|int $workshop, Closure $callback): mixed
    {
        return app(self::class)->runAs($workshop instanceof Workshop ? $workshop->id : $workshop, $callback);
    }

    private function resolve(): ?int
    {
        if ($this->explicit !== null) {
            return $this->explicit;
        }

        $user = auth()->user();

        return $user instanceof User ? $user->workshop_id : null;
    }

    /**
     * @template TReturn
     *
     * @param  Closure(): TReturn  $callback
     * @return TReturn
     */
    private function runAs(int $workshopId, Closure $callback): mixed
    {
        $previous = $this->explicit;
        $this->explicit = $workshopId;

        try {
            return $callback();
        } finally {
            $this->explicit = $previous;
        }
    }
}

<?php

declare(strict_types=1);

namespace App\Services\Sizes;

use App\Models\Size;
use Closure;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Where a size's name meets another size of its workshop (INV-18, ADR-0028). Every look-up goes through the `Size` model, so it stays
 * inside the workshop (ADR-0013). The request rule and the write guard share it, so a lost race comes back as the very message the
 * rule would have given a moment earlier.
 */
final class SizeClash
{
    /** The stored name of the size (lowest id) that holds `$label` ignoring capitals, leaving out `$ignore`; null when none does. */
    public static function nameTakenBy(string $label, ?Size $ignore = null): ?string
    {
        $stored = Size::query()
            ->whereRaw('lower(label) = lower(?)', [$label])
            ->when($ignore !== null, fn ($query) => $query->whereKeyNot($ignore?->getKey()))
            ->orderBy('id')
            ->value('label');

        return $stored === null ? null : (string) $stored;
    }

    public static function nameMessage(string $storedName): string
    {
        return __('You already have a size named ":name".', ['name' => $storedName]);
    }

    /**
     * Runs a write that saves `$label` in a transaction (a savepoint inside a surrounding one, so PostgreSQL's aborted state is
     * rolled back) and turns a hit on the unique index `sizes_workshop_label_unique` into the validation error on `label`. Any other
     * unique violation is not ours to explain and is thrown again.
     *
     * @template TReturn
     *
     * @param  Closure(): TReturn  $write
     * @return TReturn
     *
     * @throws ValidationException
     */
    public static function saving(Closure $write, string $label, ?Size $ignore = null): mixed
    {
        try {
            return DB::transaction($write);
        } catch (UniqueConstraintViolationException $e) {
            // The wrapped PDOException holds the engine's own message; the QueryException one also carries the SQL and its bindings.
            $message = $e->getPrevious()?->getMessage() ?? $e->getMessage();

            if (! str_contains($message, 'sizes_workshop_label_unique')) {
                throw $e;
            }

            // The failed statement was rolled back, so this reads the committed rows; the submitted name stands in if the clash moved.
            throw ValidationException::withMessages(['label' => self::nameMessage(self::nameTakenBy($label, $ignore) ?? $label)]);
        }
    }
}

<?php

declare(strict_types=1);

namespace App\Services\Reference;

use App\Rules\UniqueActiveName;
use Closure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * The write side of `UniqueActiveName` (F-33). Validation can't stop two people saving the same new name at the same moment: both
 * pass, and the second write meets the partial unique index `<table>_active_name_unique ON <table> (lower(name)) WHERE archived_at IS NULL`.
 * The reference Actions run their write through this guard, which turns that one violation (and a clash on `code`, unique among all
 * rows) into the validation error the user would have got a moment earlier, with the same message, so Inertia sends them back to the form
 * with `errors.name` or `errors.code`. Any other unique violation is not ours to explain and is thrown again as it came.
 *
 * The write runs in `DB::transaction()`: a savepoint inside a surrounding transaction, so a failed statement leaves no half-written row
 * and PostgreSQL's aborted-transaction state is rolled back before the guard looks anything up.
 *
 * How the violation is recognised (the PDO message, never the SQL text that Laravel appends to it):
 *  - PostgreSQL: `duplicate key value violates unique constraint "units_active_name_unique"` / `"units_code_unique"`
 *  - SQLite: `UNIQUE constraint failed: index 'units_active_name_unique'` (an expression index has no column to name) /
 *    `UNIQUE constraint failed: units.code`
 */
final class ReferenceNameGuard
{
    /**
     * Create or update: a name clash gets the "unique" message of the validation rule, a code clash the same message on `code`.
     *
     * @template TReturn
     *
     * @param  non-empty-string  $table  the reference table
     * @param  Closure(): TReturn  $write
     * @return TReturn
     *
     * @throws ValidationException
     */
    public static function saving(string $table, Closure $write): mixed
    {
        try {
            return DB::transaction($write);
        } catch (UniqueConstraintViolationException $e) {
            if (self::violates($e, "{$table}_active_name_unique")) {
                throw ValidationException::withMessages(['name' => UniqueActiveName::duplicateMessage('name')]);
            }

            if (self::violatesCode($e, $table)) {
                throw ValidationException::withMessages(['code' => UniqueActiveName::duplicateMessage('code')]);
            }

            throw $e;
        }
    }

    /**
     * Restore: a name clash gets the message of the restore request, which names the active row that took the name.
     *
     * @template TReturn
     *
     * @param  non-empty-string  $table  the reference table
     * @param  non-empty-string  $noun  what the row is: "unit", "material category", "product category"
     * @param  Model  $item  the archived row being restored
     * @param  Closure(): TReturn  $write
     * @return TReturn
     *
     * @throws ValidationException
     */
    public static function restoring(string $table, string $noun, Model $item, Closure $write): mixed
    {
        try {
            return DB::transaction($write);
        } catch (UniqueConstraintViolationException $e) {
            if (! self::violates($e, "{$table}_active_name_unique")) {
                throw $e;
            }

            // The failed statement was rolled back, so this reads the committed row that won the race. It can be gone again
            // (archived or renamed in the meantime): then there is no row to name, and the generic "already taken" message stands.
            $clash = UniqueActiveName::clashingName($table, (string) $item->getAttribute('name'), $item);

            throw ValidationException::withMessages([
                'name' => $clash === null
                    ? UniqueActiveName::duplicateMessage('name')
                    : UniqueActiveName::fill(UniqueActiveName::restoreTemplate($noun), $clash),
            ]);
        }
    }

    private static function violatesCode(UniqueConstraintViolationException $e, string $table): bool
    {
        return self::violates($e, "{$table}_code_unique") || self::violates($e, "{$table}.code");
    }

    private static function violates(UniqueConstraintViolationException $e, string $constraint): bool
    {
        // The wrapped PDOException holds the engine's own message; the QueryException one also carries the SQL and its bindings.
        $message = $e->getPrevious()?->getMessage() ?? $e->getMessage();

        return str_contains($message, $constraint);
    }
}

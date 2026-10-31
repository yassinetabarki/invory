<?php

declare(strict_types=1);

namespace App\Services\Colours;

use App\Models\Colour;
use Closure;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Where a colour's name or code meets another colour of its workshop (INV-1, ADR-0018). Every look-up goes through the `Colour` model,
 * so it stays inside the workshop (no `Rule::unique`, no `DB::table`, ADR-0013), and the messages name the colour in the way.
 *
 * The request rules and the write guard share it, so a lost race (two people saving "Stone wash" at the same moment, F-33) comes back
 * as the very message the rule would have given a moment earlier. The name is folded in SQL on both sides (`lower(name) = lower(?)`),
 * exactly like the unique index `colours_workshop_name_unique ON colours (workshop_id, lower(name))`, so rule and index agree.
 */
final class ColourClash
{
    /** The stored name of the colour (lowest id) that holds `$name` ignoring capitals, leaving out `$ignore`; null when none does. */
    public static function nameTakenBy(string $name, ?Colour $ignore = null): ?string
    {
        $stored = Colour::query()
            ->whereRaw('lower(name) = lower(?)', [$name])
            ->when($ignore !== null, fn ($query) => $query->whereKeyNot($ignore?->getKey()))
            ->orderBy('id')
            ->value('name');

        return $stored === null ? null : (string) $stored;
    }

    /** The stored name of the colour that has `$code`; null when the code is free. */
    public static function codeTakenBy(string $code): ?string
    {
        $stored = Colour::query()->where('code', $code)->value('name');

        return $stored === null ? null : (string) $stored;
    }

    public static function nameMessage(string $storedName): string
    {
        return __('You already have a colour named ":name".', ['name' => $storedName]);
    }

    public static function codeMessage(string $code, string $storedName): string
    {
        return __('Code :code is already used by :name.', ['code' => $code, 'name' => $storedName]);
    }

    /**
     * The name rule: free in the workshop ignoring capitals (a colour may be renamed to its own name in other capitals).
     *
     * @return Closure(string, mixed, Closure(string): mixed): void
     */
    public static function nameRule(?Colour $ignore = null): Closure
    {
        return static function (string $attribute, mixed $value, Closure $fail) use ($ignore): void {
            // The other rules report a missing or non-text name.
            if (! is_string($value) || $value === '') {
                return;
            }

            $stored = self::nameTakenBy($value, $ignore);

            if ($stored !== null) {
                $fail(self::nameMessage($stored));
            }
        };
    }

    /**
     * The code rule (Add colour only): free in the workshop.
     *
     * @return Closure(string, mixed, Closure(string): mixed): void
     */
    public static function codeRule(): Closure
    {
        return static function (string $attribute, mixed $value, Closure $fail): void {
            // The other rules report a missing or non-text code.
            if (! is_string($value) || $value === '') {
                return;
            }

            $stored = self::codeTakenBy($value);

            if ($stored !== null) {
                $fail(self::codeMessage($value, $stored));
            }
        };
    }

    /**
     * The write side of the two rules. Validation can't stop two people saving the same new name or code at the same moment: both pass,
     * and the second write meets a unique index. This runs the write in `DB::transaction()` (a savepoint inside a surrounding one, so a
     * failed statement leaves no half-written row and PostgreSQL's aborted state is rolled back first), looks the clash up again and
     * throws the validation error on `name` or `code`. Any other unique violation is not ours to explain and is thrown again.
     *
     * How the violation is recognised (the PDO message, never the SQL text Laravel appends to it):
     *  - PostgreSQL: `duplicate key value violates unique constraint "colours_workshop_name_unique"` / `"colours_workshop_code_unique"`
     *  - SQLite: `UNIQUE constraint failed: index 'colours_workshop_name_unique'` / `UNIQUE constraint failed: colours.workshop_id, colours.code`
     *
     * @template TReturn
     *
     * @param  Closure(): TReturn  $write
     * @param  string  $name  the name being saved
     * @param  string|null  $code  the code being saved (Add colour only)
     * @param  Colour|null  $ignore  the colour being updated, which doesn't clash with itself
     * @return TReturn
     *
     * @throws ValidationException
     */
    public static function saving(Closure $write, string $name, ?string $code = null, ?Colour $ignore = null): mixed
    {
        try {
            return DB::transaction($write);
        } catch (UniqueConstraintViolationException $e) {
            // The failed statement was rolled back, so these read the committed rows. The clashing name can have been changed again
            // in the meantime (a colour is never removed, so a clashing code stays): then the submitted name stands in for it.
            if (self::violates($e, 'colours_workshop_name_unique')) {
                throw ValidationException::withMessages(['name' => self::nameMessage(self::nameTakenBy($name, $ignore) ?? $name)]);
            }

            if ($code !== null && (self::violates($e, 'colours_workshop_code_unique') || self::violates($e, 'colours.code'))) {
                $stored = self::codeTakenBy($code);

                if ($stored !== null) {
                    throw ValidationException::withMessages(['code' => self::codeMessage($code, $stored)]);
                }
            }

            throw $e;
        }
    }

    private static function violates(UniqueConstraintViolationException $e, string $constraint): bool
    {
        // The wrapped PDOException holds the engine's own message; the QueryException one also carries the SQL and its bindings.
        $message = $e->getPrevious()?->getMessage() ?? $e->getMessage();

        return str_contains($message, $constraint);
    }
}

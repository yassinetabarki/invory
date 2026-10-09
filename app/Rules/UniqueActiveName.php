<?php

declare(strict_types=1);

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

/**
 * The name of a reference row (unit, material category, product category) is unique among the ACTIVE rows of its table,
 * ignoring case: "Elastic" and "elastic" can't both be active (F-32, ADR-0008). Archived rows never clash.
 *
 * The folding happens in SQL on both sides (`lower(name) = lower(?)`), exactly like the partial unique index the database keeps
 * (`<table>_active_name_unique ON <table> (lower(name)) WHERE archived_at IS NULL`), so validation and index agree on what
 * a duplicate is. PostgreSQL's `lower()` also folds non-ASCII letters and SQLite's folds ASCII only, on both sides alike.
 */
final readonly class UniqueActiveName implements ValidationRule
{
    /**
     * @param  non-empty-string  $table  the table to look in
     * @param  Model|null  $ignore  the row being updated or restored, which doesn't clash with itself
     * @param  string|null  $message  replaces the default "unique" message; `:name` stands for the name of the active row in the way
     */
    public function __construct(
        private string $table,
        private ?Model $ignore = null,
        private ?string $message = null,
    ) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        // Other rules report a missing or non-text name.
        if (! is_string($value) || $value === '') {
            return;
        }

        $clash = self::clashingName($this->table, $value, $this->ignore);

        if ($clash === null) {
            return;
        }

        $fail($this->message === null
            ? self::duplicateTemplate()
            : self::fill($this->message, $clash));
    }

    /**
     * The stored name of the active row (lowest id) that holds `$name` ignoring case, leaving out `$ignore`; null when none does.
     * The rule and the write guard that catches a lost race (ReferenceNameGuard) both look it up here, so they fold alike.
     *
     * @param  non-empty-string  $table
     */
    public static function clashingName(string $table, string $name, ?Model $ignore = null): ?string
    {
        $clash = DB::table($table)
            ->whereNull('archived_at')
            ->whereRaw('lower(name) = lower(?)', [$name])
            ->when($ignore !== null, fn ($query) => $query->where('id', '!=', $ignore?->getKey()))
            ->orderBy('id')
            ->value('name');

        return $clash === null ? null : (string) $clash;
    }

    /** The message a create or update gets: Laravel's own "unique" text, still holding `:attribute` for the validator to fill. */
    public static function duplicateTemplate(): string
    {
        return (string) __('validation.unique');
    }

    /** The same message with the attribute filled in the way the validator does, for errors raised outside of it. */
    public static function duplicateMessage(string $attribute): string
    {
        return str_replace(':attribute', Validator::make([], [])->getDisplayableAttribute($attribute), self::duplicateTemplate());
    }

    /**
     * The message a restore gets, with `:name` left in for the name of the active row in the way (see `fill()`).
     *
     * @param  non-empty-string  $noun  what the row is: "unit", "material category", "product category"
     */
    public static function restoreTemplate(string $noun): string
    {
        return (string) __('An active :noun is already named ":name". Rename one of them first.', [
            'noun' => __($noun),
            'name' => ':name',
        ]);
    }

    public static function fill(string $template, string $clashingName): string
    {
        return str_replace(':name', $clashingName, $template);
    }
}

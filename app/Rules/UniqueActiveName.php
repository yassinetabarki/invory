<?php

declare(strict_types=1);

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

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

        $clash = DB::table($this->table)
            ->whereNull('archived_at')
            ->whereRaw('lower(name) = lower(?)', [$value])
            ->when($this->ignore !== null, fn ($query) => $query->where('id', '!=', $this->ignore?->getKey()))
            ->orderBy('id')
            ->value('name');

        if ($clash === null) {
            return;
        }

        $fail($this->message === null
            ? __('validation.unique')
            : str_replace(':name', (string) $clash, $this->message));
    }
}

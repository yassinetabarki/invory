<?php

declare(strict_types=1);

namespace App\Services\Reference;

use Illuminate\Database\Query\Builder;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Exists;

/**
 * Validation rules for a reference-data code (a unit or a category, ADR-0008) sent by a form or a filter.
 */
final class ReferenceCode
{
    /**
     * A code that exists in the table and is not archived, or is the one the record already has (an archived value stays
     * valid on the record that uses it). The `or` sits in its own group so it can't escape the rule's `code = ?` condition.
     */
    public static function active(string $table, ?string $current = null): Exists
    {
        return Rule::exists($table, 'code')->where(fn (Builder $query) => $query->where(
            fn (Builder $query) => $query->whereNull('archived_at')->when($current !== null, fn (Builder $query) => $query->orWhere('code', $current)),
        ));
    }

    /** A code that exists in the table, archived or not (filters keep working on a category archived since). */
    public static function any(string $table): Exists
    {
        return Rule::exists($table, 'code');
    }
}

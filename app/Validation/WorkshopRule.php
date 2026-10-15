<?php

declare(strict_types=1);

namespace App\Validation;

use App\Services\Workshops\CurrentWorkshop;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Exists;
use Illuminate\Validation\Rules\Unique;
use LogicException;

/**
 * The only place `app/` builds `exists` and `unique` rules on a table of workshop data (ADR-0013 decision 6; `tests/Arch/WorkshopScopeTest.php`
 * fails anywhere else). Laravel's own rules query the table directly and ignore the workshop scope, so each rule here adds
 * `workshop_id = <current workshop>`, and skips soft-deleted rows when the model soft-deletes.
 *
 * An id of another workshop therefore fails exactly like an id that doesn't exist, and a SKU is free in a workshop as long as no
 * other record of that workshop uses it.
 */
final class WorkshopRule
{
    /**
     * @param  class-string<Model>  $model
     */
    public static function exists(string $model, string $column = 'id'): Exists
    {
        $rule = Rule::exists(self::table($model), $column)->where('workshop_id', self::workshopId());

        return self::softDeletes($model) ? $rule->withoutTrashed() : $rule;
    }

    /**
     * Callers chain `->ignore($record)` as they do on Laravel's rule.
     *
     * @param  class-string<Model>  $model
     */
    public static function unique(string $model, string $column): Unique
    {
        $rule = Rule::unique(self::table($model), $column)->where('workshop_id', self::workshopId());

        return self::softDeletes($model) ? $rule->withoutTrashed() : $rule;
    }

    /** @param  class-string<Model>  $model */
    private static function table(string $model): string
    {
        return (new $model)->getTable();
    }

    /** @param  class-string<Model>  $model */
    private static function softDeletes(string $model): bool
    {
        return in_array(SoftDeletes::class, class_uses_recursive($model), true);
    }

    /**
     * Never silently global: a request that reaches a rule without a workshop is a bug (every data route is behind `auth`).
     */
    private static function workshopId(): int
    {
        return CurrentWorkshop::id() ?? throw new LogicException('A workshop rule needs a current workshop: sign in, or run the code with CurrentWorkshop::run().');
    }
}

<?php

declare(strict_types=1);

namespace App\Models\Scopes;

use App\Services\Workshops\CurrentWorkshop;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

/**
 * Limits every query of an owned model to the current workshop (ADR-0013 decision 3): lists, subqueries, eager loads, `toBase()`
 * aggregates and route-model binding all go through it, so another workshop's record is simply not found.
 *
 * With no workshop (migrations, seeders, console commands, test set-up) it adds nothing: every route that reads owned data sits
 * behind `auth`, so a web request always has one.
 *
 * @implements Scope<Model>
 */
final class WorkshopScope implements Scope
{
    /** @param  Builder<covariant Model>  $builder */
    public function apply(Builder $builder, Model $model): void
    {
        $workshopId = CurrentWorkshop::id();

        if ($workshopId !== null) {
            $builder->where($model->qualifyColumn('workshop_id'), $workshopId);
        }
    }
}

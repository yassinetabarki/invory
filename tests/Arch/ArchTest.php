<?php

arch()->preset()->php();
arch()->preset()->security();
arch()->preset()->laravel();

arch('costing domain is framework-free')
    ->expect('App\Domain')
    ->not->toUse(['Illuminate', 'App\Models', 'App\Http']);

arch('controllers never touch the DB facade')
    ->expect('App\Http\Controllers')
    ->not->toUse('Illuminate\Support\Facades\DB');

arch('actions are final')
    ->expect('App\Actions')
    ->classes()
    ->toBeFinal()
    ->ignoring('App\Actions\Fortify');

arch('actions expose a handle method')
    ->expect('App\Actions')
    ->classes()
    ->toHaveMethod('handle')
    ->ignoring('App\Actions\Fortify');

// ADR-0006: the preview fixture seam. Domain, Actions, Models, Resources and Services never depend on it, so removing it
// (S-17) only touches controller bodies, the temporary FormRequest rules and the seeders. `App\Mock` itself is listed
// because its classes use each other.
arch('the preview data layer is only used by controllers, form requests and seeders')
    ->expect('App\Mock')
    ->toOnlyBeUsedIn([
        'App\Mock',
        'App\Http\Controllers',
        'App\Http\Requests',
        'Database\Seeders',
    ]);

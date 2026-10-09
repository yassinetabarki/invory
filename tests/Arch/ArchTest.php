<?php

arch()->preset()->php();
arch()->preset()->security();
arch()->preset()->laravel();

arch('costing domain is framework-free')
    ->expect('App\Domain')
    ->not->toUse(['Illuminate', 'App\Models', 'App\Http']);

// The costing adapter moves values between Eloquent and the domain; the maths stays in App\Domain\Costing (ADR-0002, ADR-0004).
arch('the costing service does no arithmetic of its own')
    ->expect('App\Services\Costing')
    ->not->toUse(['Brick\Math', 'bcadd', 'bcsub', 'bcmul', 'bcdiv', 'floor', 'round']);

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
// because its classes use each other. Since S-04b the products, their compositions and the demo products are real rows: what
// is left in `App\Mock` (sales, stock history, photo swatches, and the adapter that feeds the dashboard, the material usage and
// the sales form from the real products) is read by controllers only.
arch('actions, models, services and resources never use the preview data layer')
    ->expect(['App\Actions', 'App\Models', 'App\Services', 'App\Domain', 'App\Http\Resources', 'App\Policies', 'App\Enums'])
    ->not->toUse('App\Mock');

arch('the preview data layer is only used by controllers, form requests and seeders')
    ->expect('App\Mock')
    ->toOnlyBeUsedIn([
        'App\Mock',
        'App\Http\Controllers',
        'App\Http\Requests',
        'Database\Seeders',
    ]);

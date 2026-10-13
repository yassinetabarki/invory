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

// ADR-0006 is retired (S-17): the UI-first preview layer is gone, and nothing may bring a fixture seam back into the application.
arch('nothing in the application uses the removed preview data layer')
    ->expect('App')
    ->not->toUse('App\Mock');

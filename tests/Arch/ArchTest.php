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

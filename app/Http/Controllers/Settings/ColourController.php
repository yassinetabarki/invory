<?php

declare(strict_types=1);

namespace App\Http\Controllers\Settings;

use App\Actions\Colours\CreateColour;
use App\Actions\Colours\UpdateColour;
use App\Http\Controllers\Controller;
use App\Http\Requests\Colours\StoreColourRequest;
use App\Http\Requests\Colours\UpdateColourRequest;
use App\Models\Colour;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Settings > Colours (INV-1, ADR-0018). Any member manages the workshop's colours, through ColourPolicy. A colour is never deleted,
 * archived or restored, and its code is only ever given when it is added.
 *
 * The whole list in one page, in list order (position, name, id): a workshop has tens of colours, never pages of them.
 */
final class ColourController extends Controller
{
    public function index(): Response
    {
        Gate::authorize('viewAny', Colour::class);

        return Inertia::render('settings/colours', [
            'colours' => fn (): array => Colour::query()
                // INV-1-e: ->withCount('products') once `product_colours` exists, and `usage_count` becomes `$colour->products_count`.
                ->ordered()
                ->get()
                ->map(fn (Colour $colour): array => [
                    'id' => $colour->id,
                    'code' => $colour->code,
                    'name' => $colour->name,
                    'swatch' => $colour->swatch,
                    'position' => $colour->position,
                    'usage_count' => 0,
                ])
                ->all(),
        ]);
    }

    public function store(StoreColourRequest $request, CreateColour $createColour): RedirectResponse
    {
        $createColour->handle($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Colour created.')]);

        return back();
    }

    public function update(UpdateColourRequest $request, Colour $colour, UpdateColour $updateColour): RedirectResponse
    {
        $updateColour->handle($colour, $request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Colour updated.')]);

        return back();
    }
}

<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\Materials\AddMaterialColour;
use App\Http\Requests\Materials\StoreMaterialColourRequest;
use App\Models\Material;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;

final class MaterialColourController extends Controller
{
    /** StoreMaterialColourRequest authorizes `update` on the material and validates the colour and, with cost per colour, its cost. */
    public function store(StoreMaterialColourRequest $request, Material $material, AddMaterialColour $addMaterialColour): RedirectResponse
    {
        $user = $request->user();
        $cost = $request->validated('unit_cost');
        $row = $addMaterialColour->handle($material, (int) $request->validated('colour_id'), is_string($cost) ? $cost : null, $user instanceof User ? $user : abort(401));
        $colour = $row->colour->name;

        // The action opens the Restock sheet on that colour (the page's own sheet, or the one `materials.restock-sheet` feeds).
        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __(':colour added to :name.', ['colour' => $colour, 'name' => $material->name]),
            'action' => [
                'label' => __('Restock :colour', ['colour' => $colour]),
                'restock' => ['material_id' => $material->id, 'colour_id' => $row->colour_id, 'material_name' => $material->name],
            ],
        ]);

        return back();
    }
}

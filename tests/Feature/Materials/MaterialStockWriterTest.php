<?php

use App\Actions\Materials\MaterialStockWriter;
use App\Enums\MaterialCostMode;
use App\Models\Colour;
use App\Models\Material;
use App\Models\MaterialColour;
use Illuminate\Support\Facades\DB;

/*
|--------------------------------------------------------------------------
| The single writer of a material's quantities and costs (INV-21-b, ADR-0029 section 3)
|--------------------------------------------------------------------------
|
| Denim 12oz per colour with cost per colour: Indigo 250 m at 8.5000, Black 40 m at 9.2000. After every write the material row holds the
| exact total of its colours and their lowest cost, and its `colours` relation is current.
*/

it('keeps the total and the lowest cost of a per-colour material in step with its colours', function () {
    $writer = app(MaterialStockWriter::class);

    // RefreshDatabase wraps the test in a transaction: step out of it for a moment to prove that the writer wants one.
    DB::rollBack();

    try {
        expect(fn () => $writer->lock(new Material))->toThrow(LogicException::class, 'open transaction');
    } finally {
        DB::beginTransaction();
    }

    $denim = Material::factory()->perColour([['IND', '250.0000', '8.5000'], ['BLK', '40.0000', '9.2000']], MaterialCostMode::PerColour)->create();
    $colours = Colour::query()->whereIn('code', ['IND', 'BLK', 'LWH'])->get()->keyBy('code');
    [$indigo, $black, $lightWash] = [$colours['IND'], $colours['BLK'], $colours['LWH']];

    DB::transaction(function () use ($writer, $denim, $indigo, $black, $lightWash): void {
        $material = $writer->lock($denim);

        // Indigo goes to 216.12: the total follows, the cost does not move.
        $writer->setQuantity($material, $indigo->id, '216.12');

        expect($material->quantity_on_hand)->toBe('256.1200')
            ->and($material->unit_cost)->toBe('8.5000')
            ->and($material->colours->firstWhere('colour_id', $indigo->id)->quantity_on_hand)->toBe('216.1200');

        // The lowest cost follows a changed colour cost, up and down.
        $writer->setColourCost($material, $indigo->id, '9.5000');
        expect($material->unit_cost)->toBe('9.2000');

        $writer->setColourCost($material, $black->id, '10.0000');
        expect($material->unit_cost)->toBe('9.5000');

        // A result below 0 is refused and writes nothing.
        expect(fn () => $writer->addQuantity($material, $black->id, '-40.0001'))->toThrow(LogicException::class, 'below 0');
        $writer->addQuantity($material, $black->id, '-10');
        expect($material->quantity_on_hand)->toBe('246.1200');

        // A colour comes and goes; a per-colour material has no "one stock" to write.
        $writer->addColour($material, $lightWash->id, '12.5', '8.0000');
        expect($material->quantity_on_hand)->toBe('258.6200')
            ->and($material->unit_cost)->toBe('8.0000')
            ->and(fn () => $writer->setQuantity($material, null, '1'))->toThrow(LogicException::class, 'per colour');

        $writer->removeColour($material, $lightWash->id);
        expect($material->quantity_on_hand)->toBe('246.1200')
            ->and($material->unit_cost)->toBe('9.5000');
    });

    expect(Material::query()->findOrFail($denim->id))
        ->quantity_on_hand->toBe('246.1200')
        ->unit_cost->toBe('9.5000')
        ->and(MaterialColour::query()->where('material_id', $denim->id)->count())->toBe(2);

    // A one-stock material is written in its own columns.
    $thread = Material::factory()->create(['quantity_on_hand' => '100.0000', 'unit_cost' => '0.0040']);

    DB::transaction(function () use ($writer, $thread): void {
        $material = $writer->lock($thread);
        $writer->addQuantity($material, null, '20.5');
        $writer->setCost($material, '0.0050');
    });

    expect(Material::query()->findOrFail($thread->id))->quantity_on_hand->toBe('120.5000')->unit_cost->toBe('0.0050');
});

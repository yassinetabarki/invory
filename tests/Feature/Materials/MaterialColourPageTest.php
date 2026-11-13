<?php

use App\Enums\MaterialCostMode;
use App\Models\Colour;
use App\Models\Material;
use App\Models\User;

use function Pest\Laravel\actingAs;

/*
|--------------------------------------------------------------------------
| The material page's sentences and figures for a material kept per colour (INV-21-j, UI card "Material page")
|--------------------------------------------------------------------------
|
| Denim 12oz, per colour with cost per colour: Indigo 250 m at 8.5000, Black 40 m at 9.2000, reorder at 100 for each colour. The server
| writes the callout, the "N of M colours need restock" line and the cost range; colours are named in the order of Settings › Colours.
*/

beforeEach(fn () => $this->user = User::factory()->create());

it('writes the colour sentences of a per-colour material and none for one stock', function (?array $colours, MaterialCostMode $costMode, array $expected, array $positions = [], string $reorder = '100') {
    foreach ($positions as $code => $position) {
        Colour::query()->where('code', $code)->update(['position' => $position]);
    }

    $factory = $colours === null ? Material::factory()->state(['quantity_on_hand' => '5']) : Material::factory()->perColour($colours, $costMode);
    $material = $factory->create(['name' => 'Denim 12oz', 'unit' => 'm', 'reorder_level' => $reorder]);

    $response = actingAs($this->user)->get(route('materials.show', $material))->assertOk();

    foreach ($expected as $path => $value) {
        expect($response->inertiaProps("material.{$path}"))->toBe($value, $path);
    }
})->with([
    'Black is low, cost per colour' => [
        [['IND', '250.0000', '8.5000'], ['BLK', '40.0000', '9.2000']],
        MaterialCostMode::PerColour,
        [
            'quantity_on_hand' => '290.0000',
            'stock_value' => '2493.0000',
            'unit_cost_range' => ['min' => '8.5000', 'max' => '9.2000'],
            'stock_callout' => ['title' => 'Black is below reorder level.', 'detail' => '40 m on hand, reorder at 100 m.'],
            'colours_restock_summary' => null,
            'restock_colours' => 'Black',
        ],
    ],
    'three colours low or out, named in list order' => [
        [['IND', '250.0000'], ['CAM', '0.0000'], ['BLK', '40.0000'], ['RST', '12.5000']],
        MaterialCostMode::Same,
        [
            'unit_cost_range' => null,
            'stock_callout' => ['title' => '3 colours are at or below reorder level.', 'detail' => 'Black 40 m, Camel 0 m and Rust 12.5 m. Reorder at 100 m.'],
            'restock_colours' => 'Black, Camel and Rust',
        ],
        ['BLK' => 1],
    ],
    'one colour out' => [
        [['IND', '250.0000'], ['CAM', '0.0000']],
        MaterialCostMode::Same,
        [
            'stock_callout' => ['title' => 'Camel is out of stock.', 'detail' => '0 m on hand, reorder at 100 m.'],
            'restock_colours' => 'Camel',
        ],
    ],
    'more than 8 colours' => [
        [['WHT', '500.0000'], ['ECR', '500.0000'], ['BEI', '10.0000'], ['CAM', '0.0000'], ['BRN', '500.0000'], ['GRY', '20.0000'],
            ['CHA', '500.0000'], ['BLK', '500.0000'], ['NAV', '500.0000']],
        MaterialCostMode::Same,
        [
            'colours_restock_summary' => '3 of 9 colours need restock',
            'stock_callout' => ['title' => '3 colours are at or below reorder level.', 'detail' => 'Beige 10 m, Camel 0 m and Grey 20 m. Reorder at 100 m.'],
        ],
    ],
    'a colour with 1,250 m is written with its thousands separator' => [
        [['IND', '1250.0000'], ['BLK', '2000.0000']],
        MaterialCostMode::Same,
        [
            'stock_callout' => ['title' => 'Indigo is below reorder level.', 'detail' => '1,250 m on hand, reorder at 1,500 m.'],
            'restock_colours' => 'Indigo',
        ],
        [],
        '1500',
    ],
    'one of 9 colours needs restock' => [
        [['WHT', '500.0000'], ['ECR', '500.0000'], ['BEI', '10.0000'], ['CAM', '500.0000'], ['BRN', '500.0000'], ['GRY', '500.0000'],
            ['CHA', '500.0000'], ['BLK', '500.0000'], ['NAV', '500.0000']],
        MaterialCostMode::Same,
        ['colours_restock_summary' => '1 of 9 colours needs restock'],
    ],
    'none of 9 colours needs restock' => [
        [['WHT', '500.0000'], ['ECR', '500.0000'], ['BEI', '500.0000'], ['CAM', '500.0000'], ['BRN', '500.0000'], ['GRY', '500.0000'],
            ['CHA', '500.0000'], ['BLK', '500.0000'], ['NAV', '500.0000']],
        MaterialCostMode::Same,
        ['colours_restock_summary' => '0 of 9 colours need restock', 'stock_callout' => null],
    ],
    'every colour fine' => [
        [['IND', '250.0000'], ['BLK', '150.0000']],
        MaterialCostMode::Same,
        ['stock_callout' => null, 'restock_colours' => null, 'colours_restock_summary' => null],
    ],
    'one stock, low' => [
        null,
        MaterialCostMode::Same,
        [
            'stock_status' => 'low',
            'unit_cost_range' => null,
            'stock_callout' => null,
            'colours_restock_summary' => null,
            'restock_colours' => null,
        ],
    ],
]);

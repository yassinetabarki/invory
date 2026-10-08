<?php

use App\Models\Material;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

use function Pest\Laravel\actingAs;

beforeEach(function () {
    $this->user = User::factory()->create();
});

it('exposes category and unit options on the create page', function () {
    actingAs($this->user)->get(route('materials.create'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('materials/create')
            ->has('categories', 12, fn (Assert $option) => $option->hasAll(['value', 'label']))
            ->has('units', 13, fn (Assert $option) => $option->hasAll(['value', 'label']))
            ->where('units.0', ['value' => 'm', 'label' => 'Metre (m)'])
            ->where('categories.0', ['value' => 'fabric', 'label' => 'Fabric']));
});

it('exposes the flat material and options on the edit page', function () {
    $material = Material::factory()->create(['unit_cost' => '8.5', 'quantity_on_hand' => '250']);

    actingAs($this->user)->get(route('materials.edit', $material))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('materials/edit')
            ->has('categories', 12)
            ->has('units', 13)
            ->has('material', fn (Assert $m) => $m->hasAll(materialContractKeys()))
            ->where('material.id', $material->id)
            ->where('material.unit_cost', '8.5000')
            ->missing('material.data'));
});

it('exposes the flat material on the show page', function () {
    $material = Material::factory()->create([
        'name' => 'Denim 12oz Indigo',
        'category' => 'fabric',
        'unit' => 'm',
        'unit_cost' => '8.5',
        'quantity_on_hand' => '250',
        'reorder_level' => null,
    ]);

    actingAs($this->user)->get(route('materials.show', $material))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('materials/show')
            ->has('material', fn (Assert $m) => $m
                ->hasAll(materialContractKeys())
                ->where('id', $material->id)
                ->where('name', 'Denim 12oz Indigo')
                ->where('category', 'fabric')
                ->where('category_label', 'Fabric')
                ->where('unit', 'm')
                ->where('unit_label', 'Metre (m)')
                ->where('unit_cost', '8.5000')
                ->where('quantity_on_hand', '250.0000')
                ->where('reorder_level', null)
                ->where('stock_value', '2125.0000')
                ->where('stock_status', 'in_stock')
                ->where('photo_url', null)
                ->where('used_in_count', 0)
                ->where('created_at', $material->created_at->toIso8601String())
                ->where('updated_at', $material->updated_at->toIso8601String()))
            ->missing('material.data'));
});

it('sends empty usage and history on the show page until S-13c fills them', function () {
    $material = Material::factory()->create();

    actingAs($this->user)->get(route('materials.show', $material))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('usage', [])
            ->where('history', [])
            ->has('usage', 0)
            ->has('history', 0));
});

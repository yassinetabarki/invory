<?php

use App\Actions\Materials\CreateMaterial;
use App\Actions\Materials\UpdateMaterial;
use App\Models\Material;
use App\Models\User;
use Illuminate\Database\Eloquent\MassAssignmentException;

use function Pest\Laravel\actingAs;

/*
|--------------------------------------------------------------------------
| created_by / updated_by on materials (S-04a, ADR-0010 audit)
|--------------------------------------------------------------------------
*/

beforeEach(function () {
    $this->author = User::factory()->create();
    $this->editor = User::factory()->create();
});

it('records the acting user as creator and updater of a new material', function () {
    $material = app(CreateMaterial::class)->handle(
        ['sku' => 'AUD-1', 'name' => 'Audit material', 'category' => 'fabric', 'unit' => 'm', 'unit_cost' => '1', 'quantity_on_hand' => '1'],
        $this->author,
    );

    expect($material->created_by)->toBe($this->author->id)
        ->and($material->updated_by)->toBe($this->author->id)
        ->and(Material::query()->find($material->id)->created_by)->toBe($this->author->id);
});

it('moves updated_by to the user who edits and never touches created_by', function () {
    $material = Material::factory()->create();
    $material->forceFill(['created_by' => $this->author->id, 'updated_by' => $this->author->id])->save();

    $updated = app(UpdateMaterial::class)->handle($material, ['name' => 'Renamed'], $this->editor);

    expect($updated->name)->toBe('Renamed')
        ->and($updated->created_by)->toBe($this->author->id)
        ->and($updated->updated_by)->toBe($this->editor->id);
});

it('records the audit columns on an update that changes nothing else', function () {
    $material = Material::factory()->create();

    $updated = app(UpdateMaterial::class)->handle($material, [], $this->editor);

    expect($updated->updated_by)->toBe($this->editor->id)
        ->and($updated->created_by)->toBeNull();
});

it('does not let the input choose the audit columns', function () {
    expect(fn () => app(CreateMaterial::class)->handle(
        ['sku' => 'AUD-2', 'name' => 'x', 'category' => 'fabric', 'unit' => 'm', 'unit_cost' => '1', 'quantity_on_hand' => '1', 'created_by' => $this->editor->id],
        $this->author,
    ))->toThrow(MassAssignmentException::class);
});

it('stores the signed-in user through the create and update routes', function () {
    actingAs($this->author)->post(route('materials.store'), validMaterialPayload());
    $material = Material::query()->firstOrFail();

    expect($material->created_by)->toBe($this->author->id)
        ->and($material->updated_by)->toBe($this->author->id);

    actingAs($this->editor)->put(route('materials.update', $material), validMaterialPayload(['name' => 'Edited']));

    $material->refresh();

    expect($material->name)->toBe('Edited')
        ->and($material->created_by)->toBe($this->author->id)
        ->and($material->updated_by)->toBe($this->editor->id);
});

it('nulls the audit columns when the user is deleted', function () {
    $material = app(CreateMaterial::class)->handle(
        ['sku' => 'AUD-3', 'name' => 'x', 'category' => 'fabric', 'unit' => 'm', 'unit_cost' => '1', 'quantity_on_hand' => '1'],
        $this->author,
    );

    $this->author->delete();

    $material->refresh();

    expect($material->created_by)->toBeNull()
        ->and($material->updated_by)->toBeNull();
});

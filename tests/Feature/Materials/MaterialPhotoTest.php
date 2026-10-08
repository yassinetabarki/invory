<?php

use App\Models\Material;
use App\Models\User;
use Illuminate\Http\UploadedFile;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertDatabaseCount;

/*
|--------------------------------------------------------------------------
| Material photos are a preview (ADR-0006, S-13c)
|--------------------------------------------------------------------------
|
| A photo or `remove_photo` is validated for real (JPG / PNG / WebP, up to 4096 KB, no SVG) but neither stored nor part of
| the material: the real create / update still saves, and the success toast says that photos weren't saved.
*/

beforeEach(function () {
    $this->user = User::factory()->create();
});

const PHOTO_SUFFIX = " Photos are a preview and weren't saved.";

dataset('invalidPhotos', [
    'an svg' => [fn () => UploadedFile::fake()->createWithContent('logo.svg', '<svg xmlns="http://www.w3.org/2000/svg"></svg>')],
    'an svg with an image mime type' => [fn () => UploadedFile::fake()->create('logo.svg', 10, 'image/svg+xml')],
    'a pdf' => [fn () => UploadedFile::fake()->create('doc.pdf', 100, 'application/pdf')],
    'a gif' => [fn () => UploadedFile::fake()->image('anim.gif')],
    'a text file sent as a jpg' => [fn () => realUpload('fake.jpg', 'not an image', 'image/jpeg')],
    'an svg sent as a jpg' => [fn () => realUpload('logo.jpg', '<svg xmlns="http://www.w3.org/2000/svg"></svg>', 'image/jpeg')],
    'a jpg over 4096 KB' => [fn () => UploadedFile::fake()->image('big.jpg')->size(4097)],
    'a png over 4096 KB' => [fn () => UploadedFile::fake()->image('big.png')->size(5000)],
    'a path, not a file' => [fn () => 'photos/shirt.jpg'],
]);

dataset('validPhotos', [
    'jpg' => [fn () => UploadedFile::fake()->image('a.jpg')],
    'jpeg' => [fn () => UploadedFile::fake()->image('a.jpeg')],
    'png' => [fn () => UploadedFile::fake()->image('a.png')],
    'webp' => [fn () => UploadedFile::fake()->image('a.webp')],
    'exactly 4096 KB' => [fn () => UploadedFile::fake()->image('a.jpg')->size(4096)],
]);

describe('store', function () {
    it('still creates the material and says the photo wasn\'t saved', function (UploadedFile $photo) {
        $response = actingAs($this->user)->post(route('materials.store'), validMaterialPayload(['photo' => $photo]));

        $material = Material::query()->where('sku', 'FAB-DEN-001')->firstOrFail();

        $response->assertSessionHasNoErrors()
            ->assertRedirect(route('materials.show', $material))
            ->assertInertiaFlash('toast', ['type' => 'success', 'message' => 'Material created.'.PHOTO_SUFFIX]);

        expect($material->name)->toBe('Denim 12oz Indigo');
    })->with('validPhotos');

    it('says the photo wasn\'t saved when remove_photo is true', function () {
        actingAs($this->user)
            ->post(route('materials.store'), validMaterialPayload(['remove_photo' => '1']))
            ->assertInertiaFlash('toast', ['type' => 'success', 'message' => 'Material created.'.PHOTO_SUFFIX]);

        assertDatabaseCount('materials', 1);
    });

    it('keeps the plain message without a photo or when remove_photo is off', function (array $override) {
        actingAs($this->user)
            ->post(route('materials.store'), validMaterialPayload($override))
            ->assertSessionHasNoErrors()
            ->assertInertiaFlash('toast', ['type' => 'success', 'message' => 'Material created.']);
    })->with([
        'no photo keys' => [[]],
        'remove_photo false' => [['remove_photo' => false]],
        'remove_photo 0' => [['remove_photo' => '0']],
        'a null photo' => [['photo' => null]],
    ]);

    it('rejects an invalid photo and creates nothing', function (mixed $photo) {
        actingAs($this->user)
            ->post(route('materials.store'), validMaterialPayload(['photo' => $photo]))
            ->assertSessionHasErrors('photo')
            ->assertInertiaFlashMissing('toast');

        assertDatabaseCount('materials', 0);
    })->with('invalidPhotos');

    it('rejects a remove_photo that is not a boolean', function () {
        actingAs($this->user)
            ->post(route('materials.store'), validMaterialPayload(['remove_photo' => 'maybe']))
            ->assertSessionHasErrors('remove_photo');

        assertDatabaseCount('materials', 0);
    });

    it('does not put the photo among the attributes of the material', function () {
        actingAs($this->user)->post(route('materials.store'), validMaterialPayload([
            'photo' => UploadedFile::fake()->image('a.jpg'),
            'remove_photo' => true,
        ]))->assertSessionHasNoErrors();

        expect(array_keys(Material::query()->firstOrFail()->getAttributes()))->not->toContain('photo', 'remove_photo', 'photo_url');
    });
});

describe('update', function () {
    it('still updates the material and says the photo wasn\'t saved', function (UploadedFile $photo) {
        $material = Material::factory()->create(['name' => 'Before']);

        actingAs($this->user)
            ->put(route('materials.update', $material), validMaterialPayload(['name' => 'After', 'photo' => $photo]))
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('materials.show', $material))
            ->assertInertiaFlash('toast', ['type' => 'success', 'message' => 'Material updated.'.PHOTO_SUFFIX]);

        expect($material->refresh()->name)->toBe('After');
    })->with('validPhotos');

    it('says the photo wasn\'t saved when remove_photo is true', function (mixed $removePhoto) {
        $material = Material::factory()->create();

        actingAs($this->user)
            ->put(route('materials.update', $material), validMaterialPayload(['remove_photo' => $removePhoto]))
            ->assertSessionHasNoErrors()
            ->assertInertiaFlash('toast', ['type' => 'success', 'message' => 'Material updated.'.PHOTO_SUFFIX]);
    })->with([true, '1', 1]);

    it('keeps the plain message without a photo or when remove_photo is off', function (array $override) {
        $material = Material::factory()->create();

        actingAs($this->user)
            ->put(route('materials.update', $material), validMaterialPayload($override))
            ->assertSessionHasNoErrors()
            ->assertInertiaFlash('toast', ['type' => 'success', 'message' => 'Material updated.']);
    })->with([
        'no photo keys' => [[]],
        'remove_photo false' => [['remove_photo' => false]],
        'remove_photo 0' => [['remove_photo' => '0']],
        'a null photo' => [['photo' => null]],
    ]);

    it('rejects an invalid photo and changes nothing', function (mixed $photo) {
        $material = Material::factory()->create(['name' => 'Untouched']);

        actingAs($this->user)
            ->put(route('materials.update', $material), validMaterialPayload(['name' => 'Changed', 'photo' => $photo]))
            ->assertSessionHasErrors('photo')
            ->assertInertiaFlashMissing('toast');

        expect($material->refresh()->name)->toBe('Untouched');
    })->with('invalidPhotos');

    it('rejects a remove_photo that is not a boolean', function () {
        $material = Material::factory()->create(['name' => 'Untouched']);

        actingAs($this->user)
            ->put(route('materials.update', $material), validMaterialPayload(['name' => 'Changed', 'remove_photo' => 'maybe']))
            ->assertSessionHasErrors('remove_photo');

        expect($material->refresh()->name)->toBe('Untouched');
    });

    it('accepts a multipart update that is sent as POST with _method=PUT', function () {
        $material = Material::factory()->create(['name' => 'Before']);

        actingAs($this->user)
            ->post(route('materials.update', $material), [
                ...validMaterialPayload(['name' => 'After', 'photo' => UploadedFile::fake()->image('a.png')]),
                '_method' => 'PUT',
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('materials.show', $material));

        expect($material->refresh()->name)->toBe('After');
    });
});

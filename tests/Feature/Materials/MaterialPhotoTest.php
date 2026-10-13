<?php

use App\Models\Material;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertDatabaseCount;

/*
|--------------------------------------------------------------------------
| Material photo input (S-13c validation, S-15-BE storage)
|--------------------------------------------------------------------------
|
| A photo or `remove_photo` is validated (JPG / PNG / WebP, up to 4096 KB, no SVG) and an invalid one changes nothing. What the
| disk and the row do with a valid one (upload, replace, remove, delete, rollback) is PhotoWritesTest.
*/

beforeEach(function () {
    $this->user = User::factory()->create();
    Storage::fake('public');
});

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
    it('creates the material and stores a photo of each allowed type, up to 4096 KB', function (UploadedFile $photo) {
        $response = actingAs($this->user)->post(route('materials.store'), validMaterialPayload(['photo' => $photo]));

        $material = Material::query()->where('sku', 'FAB-DEN-001')->firstOrFail();

        $response->assertSessionHasNoErrors()
            ->assertRedirect(route('materials.show', $material))
            ->assertInertiaFlash('toast', ['type' => 'success', 'message' => 'Material created.']);

        expect($material->photo_path)->toStartWith('materials/');
        Storage::disk('public')->assertExists($material->photo_path);
    })->with('validPhotos');

    it('rejects an invalid photo and creates nothing', function (mixed $photo) {
        actingAs($this->user)
            ->post(route('materials.store'), validMaterialPayload(['photo' => $photo]))
            ->assertSessionHasErrors('photo')
            ->assertInertiaFlashMissing('toast');

        assertDatabaseCount('materials', 0);
        expect(Storage::disk('public')->allFiles())->toBe([]);
    })->with('invalidPhotos');

    it('rejects a remove_photo that is not a boolean', function () {
        actingAs($this->user)
            ->post(route('materials.store'), validMaterialPayload(['remove_photo' => 'maybe']))
            ->assertSessionHasErrors('remove_photo');

        assertDatabaseCount('materials', 0);
    });

    it('does not put the upload or the flag among the attributes of the material', function () {
        actingAs($this->user)->post(route('materials.store'), validMaterialPayload([
            'photo' => UploadedFile::fake()->image('a.jpg'),
            'remove_photo' => true,
        ]))->assertSessionHasNoErrors();

        expect(array_keys(Material::query()->firstOrFail()->getAttributes()))->not->toContain('photo', 'remove_photo', 'photo_url');
    });
});

describe('update', function () {
    it('updates the material and stores a photo of each allowed type, up to 4096 KB', function (UploadedFile $photo) {
        $material = Material::factory()->create(['name' => 'Before']);

        actingAs($this->user)
            ->put(route('materials.update', $material), validMaterialPayload(['name' => 'After', 'photo' => $photo]))
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('materials.show', $material))
            ->assertInertiaFlash('toast', ['type' => 'success', 'message' => 'Material updated.']);

        expect($material->refresh()->name)->toBe('After')
            ->and($material->photo_path)->toStartWith('materials/');
        Storage::disk('public')->assertExists($material->photo_path);
    })->with('validPhotos');

    it('accepts remove_photo as a boolean or its numeric forms', function (mixed $removePhoto) {
        $material = Material::factory()->create();

        actingAs($this->user)
            ->put(route('materials.update', $material), validMaterialPayload(['remove_photo' => $removePhoto]))
            ->assertSessionHasNoErrors();
    })->with([true, false, '1', '0', 1, 0]);

    it('rejects an invalid photo and changes nothing', function (mixed $photo) {
        $material = Material::factory()->create(['name' => 'Untouched']);

        actingAs($this->user)
            ->put(route('materials.update', $material), validMaterialPayload(['name' => 'Changed', 'photo' => $photo]))
            ->assertSessionHasErrors('photo')
            ->assertInertiaFlashMissing('toast');

        expect($material->refresh()->name)->toBe('Untouched');
        expect(Storage::disk('public')->allFiles())->toBe([]);
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

<?php

use App\Actions\Materials\CreateMaterial;
use App\Actions\Materials\UpdateMaterial;
use App\Actions\Products\CreateProduct;
use App\Actions\Products\UpdateProduct;
use App\Models\Material;
use App\Models\Product;
use App\Models\ProductComponent;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\QueryException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

use function Pest\Laravel\actingAs;

/*
|--------------------------------------------------------------------------
| Photos on the public disk (S-15-BE, D-5) and the composition rule of F-36
|--------------------------------------------------------------------------
|
| Material and product photos are written by the same code, so one dataset runs every case over both. The disk is faked: the
| assertions are about which files exist, never about image content. Validation is MaterialPhotoTest and ProductWriteTest.
*/

beforeEach(function () {
    $this->user = User::factory()->create();
    Storage::fake('public');
});

/** The store/update payload of a product. A multipart body cannot carry an empty list, so `components` is left out. */
function photoProductPayload(array $overrides = []): array
{
    return [
        'sku' => 'PRD-PHOTO-1',
        'name' => 'Photo Tee',
        'category' => 'apparel',
        'status' => 'active',
        'labor_cost' => '1',
        'overhead_cost' => '1',
        ...$overrides,
    ];
}

/**
 * What a material and a product share, so one test covers both: the folder, a row (optionally with a stored photo), and the
 * requests that create, update and delete one.
 *
 * @return array{directory: string, make: Closure, create: Closure, update: Closure, destroy: Closure}
 */
function photoSubject(string $kind, User $user): array
{
    $material = $kind === 'material';

    return [
        'directory' => $material ? 'materials' : 'products',
        // A row that holds a photo the disk holds too.
        'make' => function (?string $photo) use ($material): Material|Product {
            if ($photo !== null) {
                Storage::disk('public')->put($photo, 'old');
            }

            return $material
                ? Material::factory()->create(['photo_path' => $photo])
                : Product::factory()->create(['photo_path' => $photo]);
        },
        'create' => fn (array $extra) => $material
            ? actingAs($user)->post(route('materials.store'), validMaterialPayload($extra))
            : actingAs($user)->post(route('products.store'), photoProductPayload($extra)),
        'update' => fn (Material|Product $row, array $extra) => $material
            ? actingAs($user)->put(route('materials.update', $row), validMaterialPayload(['sku' => $row->sku, ...$extra]))
            : actingAs($user)->put(route('products.update', $row), photoProductPayload(['sku' => $row->sku, ...$extra])),
        'destroy' => fn (Material|Product $row) => $material
            ? actingAs($user)->delete(route('materials.destroy', $row))
            : actingAs($user)->delete(route('products.destroy', $row)),
    ];
}

it('keeps the files and the rows in step on upload, replace, remove, keep and delete', function (string $kind, string $case) {
    $subject = photoSubject($kind, $this->user);
    $directory = $subject['directory'];
    $disk = Storage::disk('public');
    $old = "{$directory}/old.jpg";

    if ($case === 'upload') {
        $subject['create'](['photo' => UploadedFile::fake()->image('new.jpg')])->assertSessionHasNoErrors();

        $row = $kind === 'material' ? Material::query()->firstOrFail() : Product::query()->firstOrFail();

        // The original is on the public disk under its folder, and `photo_url` is that disk's URL.
        expect($row->photo_path)->toStartWith("{$directory}/")
            ->and($row->photo_url)->toBe($disk->url($row->photo_path))
            ->and($disk->allFiles())->toBe([$row->photo_path]);

        return;
    }

    $row = $subject['make']($old);

    match ($case) {
        // The new photo wins over `remove_photo`, and the old file is gone.
        'replace' => $subject['update']($row, ['photo' => UploadedFile::fake()->image('new.png'), 'remove_photo' => '1']),
        'remove' => $subject['update']($row, ['remove_photo' => '1']),
        'keep' => $subject['update']($row, ['remove_photo' => '0']),
        'delete' => $subject['destroy']($row),
    };

    $row = $row::withTrashed()->findOrFail($row->id);

    match ($case) {
        'replace' => expect($row->photo_path)->toStartWith("{$directory}/")->not->toBe($old)
            ->and($disk->allFiles())->toBe([$row->photo_path]),
        'remove' => expect($row->photo_path)->toBeNull()->and($row->photo_url)->toBeNull()->and($disk->allFiles())->toBe([]),
        'keep' => expect($row->photo_path)->toBe($old)->and($disk->allFiles())->toBe([$old]),
        'delete' => expect($row->trashed())->toBeTrue()->and($row->photo_path)->toBeNull()->and($disk->allFiles())->toBe([]),
    };
})->with(['material', 'product'])->with(['upload', 'replace', 'remove', 'keep', 'delete']);

it('leaves no orphan file when the write fails, and keeps the old photo', function (string $kind) {
    $subject = photoSubject($kind, $this->user);
    $disk = Storage::disk('public');
    $old = "{$subject['directory']}/old.jpg";
    $row = $subject['make']($old);
    $other = $kind === 'material' ? Material::factory()->create() : Product::factory()->create();
    $upload = fn () => UploadedFile::fake()->image('new.jpg');

    // A SKU another row already uses: the database refuses it inside the Action's transaction, after the file was written.
    $create = fn () => match ($kind) {
        'material' => app(CreateMaterial::class)->handle([...validMaterialPayload(['sku' => $other->sku]), 'photo' => $upload()], $this->user),
        'product' => app(CreateProduct::class)->handle([...productData(['sku' => $other->sku]), 'photo' => $upload()], $this->user),
    };
    $update = fn () => match ($kind) {
        'material' => app(UpdateMaterial::class)->handle($row, [...validMaterialPayload(['sku' => $other->sku]), 'photo' => $upload()], $this->user),
        'product' => app(UpdateProduct::class)->handle($row, [...productData(['sku' => $other->sku]), 'photo' => $upload()], $this->user),
    };

    expect($create)->toThrow(QueryException::class)
        ->and($disk->allFiles())->toBe([$old]);

    expect($update)->toThrow(QueryException::class)
        ->and($disk->allFiles())->toBe([$old])
        ->and($row->fresh()->photo_path)->toBe($old);
})->with(['material', 'product']);

it('clears the composition on a multipart update that has no components (F-36) and stores the photo', function () {
    $product = Product::factory()->withComponents(2)->create();

    expect(ProductComponent::query()->where('product_id', $product->id)->count())->toBe(2);

    actingAs($this->user)
        ->post(route('products.update', $product), [
            ...photoProductPayload(['sku' => $product->sku, 'photo' => UploadedFile::fake()->image('a.webp')]),
            '_method' => 'PUT',
        ])
        ->assertSessionHasNoErrors();

    expect(ProductComponent::query()->where('product_id', $product->id)->count())->toBe(0)
        ->and(Storage::disk('public')->allFiles())->toBe([$product->refresh()->photo_path]);
});

it('adds and removes materials.photo_path with its migration, keeping the rows and the partial SKU index', function () {
    /** @var Migration $migration */
    $migration = require database_path('migrations/2026_10_13_200000_add_photo_path_to_materials_table.php');
    // Newest first: the S-44-b migration re-creates the SKU index per workshop, and this older one would otherwise put the global one back.
    /** @var Migration $workshops */
    $workshops = require database_path('migrations/2026_10_15_100100_add_workshop_to_owned_tables.php');
    Material::factory()->create(['sku' => 'KEEP-1', 'name' => 'Survivor', 'photo_path' => 'materials/a.jpg']);

    expect(Schema::hasColumn('materials', 'photo_path'))->toBeTrue();

    // Newer migrations that depend on `(workshop_id, id)` (orders) must be rolled back first.
    ordersMigration()->down();
    $workshops->down();
    $migration->down();

    expect(Schema::hasColumn('materials', 'photo_path'))->toBeFalse()
        ->and(DB::table('materials')->where('sku', 'KEEP-1')->value('name'))->toBe('Survivor')
        ->and(Schema::hasIndex('materials', 'materials_sku_unique'))->toBeTrue();

    $migration->up();
    $workshops->up();
    ordersMigration()->up();

    expect(Schema::hasColumn('materials', 'photo_path'))->toBeTrue()
        ->and(DB::table('materials')->where('sku', 'KEEP-1')->value('photo_path'))->toBeNull()
        ->and(Schema::hasIndex('materials', 'materials_sku_unique'))->toBeTrue();
});

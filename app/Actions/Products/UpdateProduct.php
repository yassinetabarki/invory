<?php

declare(strict_types=1);

namespace App\Actions\Products;

use App\Actions\Barcodes\AddNameToBarcodes;
use App\Domain\Barcodes\NameKey;
use App\Models\Product;
use App\Models\User;
use App\Models\Workshop;
use App\Services\Barcodes\RenameClash;
use App\Services\Photos\PhotoStorage;
use App\Services\Products\ProductColourLock;
use App\Services\Products\ProductSizeLock;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final readonly class UpdateProduct
{
    public function __construct(
        private ResolveProductCategoryCode $resolveCategory,
        private SyncProductComponents $syncComponents,
        private SyncProductColours $syncColours,
        private SyncProductSizes $syncSizes,
        private PhotoStorage $photos,
        private ProductColourLock $colourLock,
        private ProductSizeLock $sizeLock,
        private RenameClash $renameClash,
        private AddNameToBarcodes $addName,
    ) {}

    /**
     * The composition is ALWAYS replaced by the submitted `components` (F-36): the product form submits its whole state, and a
     * missing or null `components` means an empty composition (a multipart body, sent when a photo is attached, can't carry an
     * empty list). A caller that means to keep the composition must send it.
     *
     * A `photo` upload replaces the photo (it wins over `remove_photo`) and `remove_photo` clears it. The new file is stored
     * before the transaction and deleted again if it throws; the old file is deleted after it committed.
     *
     * @param  array<string, mixed>  $data  validated input: the product fields (`category` as a code), its `components`, its `colours` and `sizes` (ids, each left alone when absent) and `photo` / `remove_photo`
     */
    public function handle(Product $product, array $data, User $actor): Product
    {
        return $this->photos->persist('products', $data, $product->photo_path, fn (array $attributes, array $photo): Product => DB::transaction(function () use ($product, $attributes, $photo, $actor): Product {
            // The product's row lock, once for both locks below and for the writes after them (ADR-0017 decision 4).
            $locked = Product::query()->lockForUpdate()->findOrFail($product->id);

            $sizeIds = $this->sizeIds($attributes);

            $this->guardColours($locked, $attributes['colours'] ?? null);
            $this->guardSizes($locked, $sizeIds);
            $this->guardName($locked, $attributes, $sizeIds);

            $product->fill($this->resolveCategory->handle(Arr::except($attributes, ['components', 'colours', 'sizes'])))->forceFill($photo);
            $product->updated_by = $actor->id;
            $product->save();

            // Only when the input carries colours: a caller that does not mean to change them (a seeder, an older test) keeps them.
            if (array_key_exists('colours', $attributes)) {
                $this->syncColours->handle($product, $attributes['colours']);
            }

            // INV-18-f: likewise for the sizes (a category change never touches them).
            if ($sizeIds !== null) {
                $this->syncSizes->handle($product, $sizeIds);
            }

            // INV-8-d: a rename keeps the barcodes of the old name for the colours and sizes the product has now (nothing for the same key).
            $this->addName->handle($product, $locked->name_key);

            $this->syncComponents->handle($product, $attributes['components'] ?? []);

            return $product->refresh();
        }));
    }

    /**
     * INV-8-d / AC-5, the second check: when the name's key changes, the workshop's row lock (the order every barcode writer uses:
     * product, then workshop), then the clash of numbers is looked for again with the colours this save leaves the product
     * with, so a barcode made while the form was open is caught. Same key (capitals or spaces only): no lock, nothing to check.
     *
     * @param  array<string, mixed>  $attributes
     * @param  list<int>|null  $sizeIds  the sizes the save leaves the product with; null when it keeps them
     */
    private function guardName(Product $locked, array $attributes, ?array $sizeIds): void
    {
        $name = $attributes['name'] ?? null;

        if (! is_string($name) || NameKey::of($name) === $locked->name_key) {
            return;
        }

        Workshop::query()->lockForUpdate()->findOrFail($locked->workshop_id);

        $colourIds = array_key_exists('colours', $attributes) && is_array($attributes['colours'])
            ? array_values(array_map(fn (mixed $id): int => (int) (is_scalar($id) ? $id : 0), $attributes['colours']))
            : array_values($locked->colours()->pluck('colours.id')->map(fn (mixed $id): int => (int) $id)->all());

        $refusal = $this->renameClash->refusal($locked, $name, $colourIds, $sizeIds);

        if ($refusal !== null) {
            throw ValidationException::withMessages(['name' => $refusal]);
        }
    }

    /**
     * The sizes the input carries, as ids; null when it carries none (the product keeps its own).
     *
     * @param  array<string, mixed>  $attributes
     * @return list<int>|null
     */
    private function sizeIds(array $attributes): ?array
    {
        return array_key_exists('sizes', $attributes) && is_array($attributes['sizes'])
            ? array_values(array_map(fn (mixed $id): int => (int) (is_scalar($id) ? $id : 0), $attributes['sizes']))
            : null;
    }

    /**
     * INV-18-f / AC-6, the second check: under the same lock, before the sizes are synced, so a stock update, an order, a barcode or a
     * batch can't land between the request and the save. One sentence per locked size taken off, on `sizes.removed.{sizeId}`, in list
     * order, as the request puts them.
     *
     * @param  list<int>|null  $sizeIds  the sizes the product keeps; null when the input carries none (they are left alone)
     */
    private function guardSizes(Product $locked, ?array $sizeIds): void
    {
        if ($sizeIds === null) {
            return;
        }

        $refusals = $this->sizeLock->refusals($locked, $sizeIds);

        if ($refusals !== []) {
            throw ValidationException::withMessages(array_combine(
                array_map(fn (int $sizeId): string => "sizes.removed.{$sizeId}", array_keys($refusals)),
                $refusals,
            ));
        }
    }

    /**
     * INV-2-b, the second check: under the same lock, before the colours are synced, so a stock update and a colour change never pass
     * each other. One sentence per colour taken off that still has stock, on `colours.removed.{colourId}`, as the request puts them.
     *
     * @param  array<int|string, mixed>|null  $colourIds  the colours the product keeps; null when the input carries none (they are left alone)
     */
    private function guardColours(Product $locked, ?array $colourIds): void
    {
        if ($colourIds === null) {
            return;
        }

        $refusals = $this->colourLock->refusals($locked, array_values(array_map(fn (mixed $id): int => (int) (is_scalar($id) ? $id : 0), $colourIds)));

        if ($refusals !== []) {
            throw ValidationException::withMessages(array_combine(
                array_map(fn (int $colourId): string => "colours.removed.{$colourId}", array_keys($refusals)),
                $refusals,
            ));
        }
    }
}

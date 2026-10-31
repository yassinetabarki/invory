<?php

declare(strict_types=1);

namespace App\Actions\Products;

use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\User;
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
        private PhotoStorage $photos,
        private ProductSizeLock $sizeLock,
        private ProductColourLock $colourLock,
    ) {}

    /**
     * The composition is ALWAYS replaced by the submitted `components` (F-36): the product form submits its whole state, and a
     * missing or null `components` means an empty composition (a multipart body, sent when a photo is attached, can't carry an
     * empty list). A caller that means to keep the composition must send it.
     *
     * A `photo` upload replaces the photo (it wins over `remove_photo`) and `remove_photo` clears it. The new file is stored
     * before the transaction and deleted again if it throws; the old file is deleted after it committed.
     *
     * @param  array<string, mixed>  $data  validated input: the product fields (`category` as a code), its `components`, its `colours` (ids, left alone when absent) and `photo` / `remove_photo`
     */
    public function handle(Product $product, array $data, User $actor): Product
    {
        return $this->photos->persist('products', $data, $product->photo_path, fn (array $attributes, array $photo): Product => DB::transaction(function () use ($product, $attributes, $photo, $actor): Product {
            // The product's row lock, once for both locks below and for the writes after them (ADR-0017 decision 4).
            $locked = Product::query()->with('category')->lockForUpdate()->findOrFail($product->id);

            $this->guardSizes($locked, $attributes['category'] ?? null);
            $this->guardColours($locked, $attributes['colours'] ?? null);

            $product->fill($this->resolveCategory->handle(Arr::except($attributes, ['components', 'colours'])))->forceFill($photo);
            $product->updated_by = $actor->id;
            $product->save();

            // Only when the input carries colours: a caller that does not mean to change them (a seeder, an older test) keeps them.
            if (array_key_exists('colours', $attributes)) {
                $this->syncColours->handle($product, $attributes['colours']);
            }

            $this->syncComponents->handle($product, $attributes['components'] ?? []);

            return $product->refresh();
        }));
    }

    /**
     * S-58 / AC-4, the second check: under the product's row lock (`$locked`), so the category it has now is the one compared.
     * Throwing here rolls the transaction back and `PhotoStorage` deletes the file it stored for this save.
     */
    private function guardSizes(Product $locked, mixed $categoryCode): void
    {
        if ($categoryCode === null) {
            return;
        }

        $refusal = $this->sizeLock->refusal($locked, ProductCategory::query()->where('code', $categoryCode)->firstOrFail());

        if ($refusal !== null) {
            throw ValidationException::withMessages(['category' => $refusal]);
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

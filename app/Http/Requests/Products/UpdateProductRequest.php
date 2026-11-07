<?php

declare(strict_types=1);

namespace App\Http\Requests\Products;

use App\Models\Product;
use App\Services\Barcodes\RenameClash;
use App\Services\Products\ProductColourLock;
use App\Services\Products\ProductSizeLock;
use Illuminate\Auth\Access\Response;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Validator;

class UpdateProductRequest extends FormRequest
{
    /**
     * `{product}` is route-model bound (an unknown or soft-deleted product is a 404 before anything is validated), then the
     * policy decides.
     */
    public function authorize(): Response
    {
        $product = $this->route('product');

        // The Gate's own response, so a policy's `denyAsNotFound()` stays a 404 (ADR-0013 decision 5).
        return $product instanceof Product ? Gate::inspect('update', $product) : Response::denyAsNotFound();
    }

    protected function prepareForValidation(): void
    {
        StoreProductRequest::defaultOverheadToZero($this);
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $product = $this->route('product');

        if ($product instanceof Product) {
            // An explicit load, not a lazy one: the current category stays valid even if it was archived since.
            $product->loadMissing('category');
        }

        return StoreProductRequest::fieldRules($product instanceof Product ? $product : null);
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return StoreProductRequest::fieldMessages();
    }

    /**
     * INV-2-b: a colour with units in stock stays on the product. Each colour taken off that still has stock gets its own sentence on
     * `colours.removed.{colourId}` (Inertia shares only the first message of a key), in list order; it is checked only when the
     * colours themselves passed (`UpdateProduct` checks again under the row lock).
     *
     * INV-18-f / AC-5, AC-6: One size is chosen alone, and a size the product uses stays: each size taken off that is locked gets its own
     * sentence on `sizes.removed.{sizeId}`, in list order; checked only when `sizes` passed (`UpdateProduct` checks again under the row lock).
     *
     * INV-8-d / AC-5: a rename that would make two different barcode numbers meet is refused on `name`, with one sentence; checked only
     * when the name itself passed, with the colours and sizes the save leaves the product with (`UpdateProduct` checks again under
     * the row locks).
     *
     * @return list<callable(Validator): void>
     */
    public function after(): array
    {
        return [
            fn (Validator $validator) => StoreProductRequest::oneSizeAlone($this, $validator),
            $this->colourLock(...),
            $this->sizeLock(...),
            $this->renameClash(...),
        ];
    }

    private function sizeLock(Validator $validator): void
    {
        $product = $this->route('product');

        if (! $product instanceof Product || $validator->errors()->hasAny(['sizes', 'sizes.*'])) {
            return;
        }

        foreach (app(ProductSizeLock::class)->refusals($product, $this->sizeIds()) as $sizeId => $refusal) {
            $validator->errors()->add("sizes.removed.{$sizeId}", $refusal);
        }
    }

    /** @return list<int> the sizes the save leaves the product with */
    private function sizeIds(): array
    {
        return array_values(array_map(fn (mixed $id): int => (int) (is_scalar($id) ? $id : 0), $this->array('sizes')));
    }

    private function renameClash(Validator $validator): void
    {
        $product = $this->route('product');
        $name = $this->input('name');

        if (! $product instanceof Product || ! is_string($name) || $validator->errors()->hasAny(['name', 'colours', 'colours.*', 'sizes', 'sizes.*'])) {
            return;
        }

        $colourIds = $this->has('colours')
            ? array_values(array_map(fn (mixed $id): int => (int) (is_scalar($id) ? $id : 0), $this->array('colours')))
            : array_values($product->colours()->pluck('colours.id')->map(fn (mixed $id): int => (int) $id)->all());

        $refusal = app(RenameClash::class)->refusal($product, $name, $colourIds, $this->sizeIds());

        if ($refusal !== null) {
            $validator->errors()->add('name', $refusal);
        }
    }

    private function colourLock(Validator $validator): void
    {
        $product = $this->route('product');

        if (! $product instanceof Product || ! $this->has('colours') || $validator->errors()->hasAny(['colours', 'colours.*'])) {
            return;
        }

        $kept = array_values(array_map(fn (mixed $id): int => (int) (is_scalar($id) ? $id : 0), $this->array('colours')));

        foreach (app(ProductColourLock::class)->refusals($product, $kept) as $colourId => $refusal) {
            $validator->errors()->add("colours.removed.{$colourId}", $refusal);
        }
    }
}

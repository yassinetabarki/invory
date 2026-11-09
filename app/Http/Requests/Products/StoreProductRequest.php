<?php

declare(strict_types=1);

namespace App\Http\Requests\Products;

use App\Enums\ComponentQuantityMode;
use App\Enums\ProductStatus;
use App\Models\Colour;
use App\Models\Material;
use App\Models\Product;
use App\Models\Size;
use App\Services\Reference\ReferenceCode;
use App\Validation\WorkshopRule;
use Brick\Math\BigDecimal;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\File;
use Illuminate\Validation\Validator;

class StoreProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Product::class) ?? false;
    }

    protected function prepareForValidation(): void
    {
        self::defaultOverheadToZero($this);
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return self::fieldRules(product: null);
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return self::fieldMessages();
    }

    /** The most quantities (filled cells) one product holds over all its By colour and size lines (INV-16, ADR-0027). */
    public const MAX_QUANTITIES = 2000;

    /**
     * INV-18-f: One size is chosen alone. INV-16-e: the quantity grid agrees with the colours and sizes of the save and stays within the cap.
     *
     * @return list<callable(Validator): void>
     */
    public function after(): array
    {
        return [
            fn (Validator $validator) => self::oneSizeAlone($this, $validator),
            fn (Validator $validator) => self::quantityGrid($this, $validator),
        ];
    }

    /**
     * The messages shared with UpdateProductRequest. Only `colours` and `sizes` have their own wording; `colours.*` and `sizes.*` fail
     * only on a tampered request.
     *
     * @return array<string, string>
     */
    public static function fieldMessages(): array
    {
        $cell = __('Enter a number from 0 to 1,000,000 with up to 4 decimals.');

        return [
            'components.*.quantities.*.*.decimal' => $cell,
            'components.*.quantities.*.*.min' => $cell,
            'components.*.quantities.*.*.max' => $cell,
            'colours.required' => __('Choose at least one colour.'),
            'colours.array' => __('Choose at least one colour.'),
            'colours.min' => __('Choose at least one colour.'),
            'sizes.required' => __('Choose at least one size.'),
            'sizes.array' => __('Choose at least one size.'),
            'sizes.min' => __('Choose at least one size.'),
        ];
    }

    /**
     * INV-18-f, AC-5: the workshop's One size can't be combined with another (a stale page can send both). Shared with
     * UpdateProductRequest; checked only when `sizes` itself passed.
     */
    public static function oneSizeAlone(FormRequest $request, Validator $validator): void
    {
        if ($validator->errors()->hasAny(['sizes', 'sizes.*'])) {
            return;
        }

        $ids = array_map(fn (mixed $id): int => (int) (is_scalar($id) ? $id : 0), $request->array('sizes'));

        if (count($ids) > 1 && Size::query()->whereIn('id', $ids)->where('is_one_size', true)->exists()) {
            $validator->errors()->add('sizes', __("One size can't be combined with other sizes. Choose it alone, or choose other sizes."));
        }
    }

    /**
     * INV-16-e, AC-3: the By colour and size lines, read from the payload alone (so nothing about them is checked again under the lock).
     * On `components.{i}.quantities`, a line with no quantity ("Add at least one quantity for Denim 12oz Black, or switch it to Same
     * for every variant."); on `components`, a quantity whose colour or size is not among the save's own ("The sizes or colours of
     * Denim Jacket have changed. Enter its quantities again.") or more than 2,000 quantities over all the lines. Shared with
     * UpdateProductRequest. A cell that failed its own rule is not counted, and the checks that name the product or need the colours
     * and sizes wait for those to pass.
     */
    public static function quantityGrid(FormRequest $request, Validator $validator): void
    {
        $components = $request->input('components');

        if (! is_array($components)) {
            return;
        }

        $errors = $validator->errors();
        $filled = [];
        $empty = [];

        foreach ($components as $index => $line) {
            if (! is_array($line) || ($line['quantity_mode'] ?? null) !== ComponentQuantityMode::ByVariant->value) {
                continue;
            }

            $cells = [];
            $failed = $errors->hasAny(["components.{$index}.quantities", "components.{$index}.quantities.*"]);

            foreach (is_array($line['quantities'] ?? null) ? $line['quantities'] : [] as $colourId => $bySize) {
                foreach (is_array($bySize) ? $bySize : [] as $sizeId => $value) {
                    if ($errors->has("components.{$index}.quantities.{$colourId}.{$sizeId}")) {
                        $failed = true;
                    } elseif (self::isQuantity($value)) {
                        $cells[] = [(string) $colourId, (string) $sizeId];
                    }
                }
            }

            $filled = [...$filled, ...$cells];

            if ($cells === [] && ! $failed && ! $errors->has("components.{$index}.material_id") && is_scalar($line['material_id'] ?? null)) {
                $empty[$index] = (int) $line['material_id'];
            }
        }

        if ($empty !== []) {
            $names = Material::query()->whereKey(array_values($empty))->pluck('name', 'id');

            foreach ($empty as $index => $materialId) {
                if ($names->has($materialId)) {
                    $errors->add("components.{$index}.quantities", __('Add at least one quantity for :material, or switch it to Same for every variant.', ['material' => $names->get($materialId)]));
                }
            }
        }

        $product = self::productName($request);

        if ($product === null || $errors->hasAny(['colours', 'colours.*', 'sizes', 'sizes.*'])) {
            return;
        }

        $colours = array_map(fn (mixed $id): string => is_scalar($id) ? (string) $id : '', $request->array('colours'));
        $sizes = array_map(fn (mixed $id): string => is_scalar($id) ? (string) $id : '', $request->array('sizes'));

        foreach ($filled as [$colourId, $sizeId]) {
            if (! in_array($colourId, $colours, true) || ! in_array($sizeId, $sizes, true)) {
                $errors->add('components', __('The sizes or colours of :product have changed. Enter its quantities again.', ['product' => $product]));

                return;
            }
        }

        if (count($filled) > self::MAX_QUANTITIES) {
            $errors->add('components', __(':product can hold at most 2,000 quantities across its materials. Remove some, or switch a line to Same for every variant.', ['product' => $product]));
        }
    }

    /** Whether a cell holds a quantity: a number above 0 (empty and 0 make no cell). Only asked of values that passed their rule. */
    private static function isQuantity(mixed $value): bool
    {
        return (is_string($value) || is_int($value) || is_float($value))
            && trim((string) $value) !== ''
            && BigDecimal::of(trim((string) $value))->isPositive();
    }

    /** The name the grid's sentences use: the product's own on Edit, the submitted one on Add; null when there is none to use. */
    private static function productName(FormRequest $request): ?string
    {
        $product = $request->route('product');
        $name = $product instanceof Product ? $product->name : $request->input('name');

        return is_string($name) && trim($name) !== '' ? trim($name) : null;
    }

    /**
     * A blank overhead is saved as 0 (S-43, D-8). A blank field arrives as null (TrimStrings + ConvertEmptyStringsToNull), a missing
     * key means the same. Any other value, even a wrong one, is left alone so the rules still reject it. Shared with UpdateProductRequest.
     */
    public static function defaultOverheadToZero(FormRequest $request): void
    {
        if ($request->input('overhead_cost') === null) {
            $request->merge(['overhead_cost' => '0']);
        }
    }

    /**
     * Rules shared with UpdateProductRequest. Decimal bounds match the columns: `decimal(12,4)` for costs and prices,
     * `decimal(14,4)` for quantities, `decimal(5,2)` for the waste percentage.
     *
     * `sku` is unique among non-deleted products (the product being updated keeps its own). `category` is the code of a
     * non-archived product category; on update the product's current code stays valid too.
     *
     * @param  Product|null  $product  The product being updated (with its `category` loaded), or null when creating.
     * @return array<string, mixed>
     */
    public static function fieldRules(?Product $product): array
    {
        return [
            'sku' => ['required', 'string', 'max:64', WorkshopRule::unique(Product::class, 'sku')->ignore($product)],
            'name' => ['required', 'string', 'max:150'],
            'category' => ['required', 'string', ReferenceCode::active('product_categories', $product?->category->code)],
            'status' => ['required', Rule::enum(ProductStatus::class)],
            'description' => ['nullable', 'string', 'max:2000'],
            'labor_cost' => ['required', 'decimal:0,4', 'min:0', 'max:99999999.9999'],
            'overhead_cost' => ['nullable', 'decimal:0,4', 'min:0', 'max:99999999.9999'],
            'selling_price' => ['nullable', 'decimal:0,4', 'min:0', 'max:99999999.9999'],
            'photo' => ['nullable', File::image()->types(['jpg', 'jpeg', 'png', 'webp'])->max(4096)],
            'remove_photo' => ['nullable', 'boolean'],
            // INV-1: at least one colour of the workshop, whatever the status. A multipart body that carries none has no key at all.
            'colours' => ['bail', 'required', 'array', 'min:1'],
            'colours.*' => ['bail', 'integer', 'distinct', WorkshopRule::exists(Colour::class)],
            // INV-18-h, AC-5, AC-6: at least one size of the workshop, on Add and Edit; a missing, empty or non-array list is refused.
            'sizes' => ['bail', 'required', 'array', 'min:1'],
            'sizes.*' => ['bail', 'integer', 'distinct', WorkshopRule::exists(Size::class)],
            // The composition may be empty (a draft). It is `nullable` rather than `present` because a multipart body
            // (a photo is attached) cannot carry an empty array: the key is simply absent, and on update a missing or null list is an empty
            // composition (F-36: the composition is always replaced).
            'components' => ['nullable', 'array'],
            'components.*.material_id' => ['required', 'integer', 'distinct', WorkshopRule::exists(Material::class)],
            // INV-16-e: a line is `same` (the default) or `by_variant`. The one has its `quantity`, the other its `quantities[colourId][sizeId]`;
            // what a line doesn't use is left out of the validated input. A cell is empty or 0 (no cell) or a number up to 1,000,000.
            'components.*.quantity_mode' => ['nullable', Rule::enum(ComponentQuantityMode::class)],
            'components.*.quantity' => ['exclude_if:components.*.quantity_mode,by_variant', 'required', 'decimal:0,4', 'gt:0', 'max:9999999999.9999'],
            'components.*.quantities' => ['exclude_unless:components.*.quantity_mode,by_variant', 'nullable', 'array'],
            'components.*.quantities.*' => ['exclude_unless:components.*.quantity_mode,by_variant', 'nullable', 'array'],
            'components.*.quantities.*.*' => ['exclude_unless:components.*.quantity_mode,by_variant', 'bail', 'nullable', 'decimal:0,4', 'min:0', 'max:1000000'],
            'components.*.waste_percent' => ['nullable', 'decimal:0,2', 'min:0', 'lt:100'],
            'components.*.notes' => ['nullable', 'string', 'max:100'],
        ];
    }
}

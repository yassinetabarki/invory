<?php

declare(strict_types=1);

namespace App\Http\Requests\Products;

use App\Enums\ProductStatus;
use App\Models\Colour;
use App\Models\Material;
use App\Models\Product;
use App\Models\Size;
use App\Services\Reference\ReferenceCode;
use App\Validation\WorkshopRule;
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

    /**
     * INV-18-f: One size is chosen alone.
     *
     * @return list<callable(Validator): void>
     */
    public function after(): array
    {
        return [fn (Validator $validator) => self::oneSizeAlone($this, $validator)];
    }

    /**
     * The messages shared with UpdateProductRequest. Only `colours` and `sizes` have their own wording; `colours.*` and `sizes.*` fail
     * only on a tampered request.
     *
     * @return array<string, string>
     */
    public static function fieldMessages(): array
    {
        return [
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
            'components.*.quantity' => ['required', 'decimal:0,4', 'gt:0', 'max:9999999999.9999'],
            'components.*.waste_percent' => ['nullable', 'decimal:0,2', 'min:0', 'lt:100'],
            'components.*.notes' => ['nullable', 'string', 'max:100'],
        ];
    }
}

<?php

declare(strict_types=1);

namespace App\Http\Requests\Production;

use App\Models\Colour;
use App\Models\Product;
use App\Models\ProductionBatch;
use App\Models\Size;
use App\Models\SizeList;
use App\Services\Orders\OrderMessages;
use App\Services\Production\ProductionMessages;
use App\Services\Products\SizeLists;
use App\Services\Restocks\RestockMessages;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Start batch (INV-5-c, AC-2): a product of the workshop and its `units` by colour id then size id, every field empty or a whole
 * number from 0 to 1,000,000, at least one above 0. A field's message starts with its variant ("Indigo M: …"). Whether the product was
 * deleted, its colours or sizes changed, and the stock are checked by `StartBatch` under the locks.
 */
final class StartBatchRequest extends FormRequest
{
    private ?Product $product = null;

    private bool $productLoaded = false;

    public function authorize(): bool
    {
        return $this->user()?->can('create', ProductionBatch::class) ?? false;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'product_id' => ['bail', 'required', 'integer'],
            'units' => ['present', 'array'],
            'units.*' => ['array'],
            'units.*.*' => ['bail', 'nullable', 'integer', 'min:0', 'max:'.ProductionBatch::MAX_UNITS],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        $messages = [
            'product_id.required' => ProductionMessages::chooseProduct(),
            'product_id.integer' => ProductionMessages::chooseProduct(),
        ];

        $product = $this->product();

        if ($product !== null) {
            $sizeList = app(SizeLists::class)->category($product->category_id)->sizeList;

            foreach ($product->colours as $colour) {
                foreach ($sizeList->sizes as $size) {
                    $named = RestockMessages::wholeNumber(self::variant($colour, $size, $sizeList));

                    foreach (['integer', 'min', 'max'] as $rule) {
                        $messages["units.{$colour->id}.{$size->id}.{$rule}"] = $named;
                    }
                }
            }
        }

        $any = OrderMessages::sizeQuantity();

        return $messages + ['units.*.*.integer' => $any, 'units.*.*.min' => $any, 'units.*.*.max' => $any];
    }

    /** @return list<callable(Validator): void> */
    public function after(): array
    {
        return [function (Validator $validator): void {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            if ($this->product() === null) {
                $validator->errors()->add('product_id', ProductionMessages::chooseProduct());

                return;
            }

            if (array_sum(array_map('array_sum', $this->grid())) < 1) {
                $validator->errors()->add('units', ProductionMessages::nothingToStart());
            }
        }];
    }

    public function productId(): int
    {
        return $this->integer('product_id');
    }

    /**
     * The units as sent, each field a whole number (empty as 0). Call it once the request has passed.
     *
     * @return array<int, array<int, int>>
     */
    public function grid(): array
    {
        $grid = [];

        foreach ((array) $this->input('units', []) as $colourId => $bySize) {
            $grid[(int) $colourId] = [];

            foreach (is_array($bySize) ? $bySize : [] as $sizeId => $value) {
                $grid[(int) $colourId][(int) $sizeId] = is_numeric($value) && (int) $value == $value ? max(0, (int) $value) : 0;
            }
        }

        return $grid;
    }

    /** The product asked for, of the current workshop (the scope), deleted included, with its colours; null when there is none. */
    private function product(): ?Product
    {
        if (! $this->productLoaded) {
            $id = $this->input('product_id');
            $this->product = is_numeric($id) ? Product::withTrashed()->with('colours')->find((int) $id) : null;
            $this->productLoaded = true;
        }

        return $this->product;
    }

    private static function variant(Colour $colour, Size $size, SizeList $sizeList): string
    {
        return RestockMessages::variant($colour->name, $size->label, $sizeList->code === SizeList::ONE_SIZE);
    }
}

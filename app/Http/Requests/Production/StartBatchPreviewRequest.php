<?php

declare(strict_types=1);

namespace App\Http\Requests\Production;

use App\Models\Product;
use App\Models\ProductionBatch;
use App\Services\Orders\OrderMessages;
use App\Services\Production\ProductionMessages;
use App\Services\Products\ProductSizes;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Query of `GET progress/products/{product}/materials` (INV-5-c, INV-16-k): the window's grid, `units[colourId][sizeId]=n` with only the
 * fields typed above 0 sent, at least one unit overall. Each field is a whole number from 0 to 1,000,000; a colour or size the product
 * doesn't have is refused like Start batch refuses it.
 */
final class StartBatchPreviewRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', ProductionBatch::class) ?? false;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'units' => ['required', 'array'],
            'units.*' => ['array'],
            'units.*.*' => ['bail', 'nullable', 'integer', 'min:0', 'max:'.ProductionBatch::MAX_UNITS],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        $units = OrderMessages::sizeQuantity();

        return [
            'units.required' => $units, 'units.array' => $units, 'units.*.array' => $units,
            'units.*.*.integer' => $units, 'units.*.*.min' => $units, 'units.*.*.max' => $units,
        ];
    }

    /** @return list<callable(Validator): void> */
    public function after(): array
    {
        return [function (Validator $validator): void {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $grid = $this->units();

            if ($grid === []) {
                $validator->errors()->add('units', OrderMessages::sizeQuantity());

                return;
            }

            $product = $this->route('product');

            if ($product instanceof Product && ! $this->matches($product, $grid)) {
                $validator->errors()->add('units', ProductionMessages::startChanged($product->name));
            }
        }];
    }

    /**
     * The units asked for, by colour id then size id, only the fields above 0 (empty counts as 0). Call it once the request has passed.
     *
     * @return array<int, array<int, int>>
     */
    public function units(): array
    {
        $grid = [];

        foreach ((array) $this->input('units', []) as $colourId => $bySize) {
            foreach (is_array($bySize) ? $bySize : [] as $sizeId => $value) {
                $units = is_numeric($value) ? (int) $value : 0;

                if ($units > 0) {
                    $grid[(int) $colourId][(int) $sizeId] = $units;
                }
            }
        }

        return $grid;
    }

    /**
     * Whether every colour and size asked for is one of the product's own (a field may be missing for the others).
     *
     * @param  array<int, array<int, int>>  $grid
     */
    private function matches(Product $product, array $grid): bool
    {
        $colourIds = $product->colours()->pluck('colours.id')->map(intval(...))->all();
        $sizeIds = app(ProductSizes::class)->of($product)->ids();

        foreach ($grid as $colourId => $bySize) {
            if (! in_array($colourId, $colourIds, true) || array_diff(array_keys($bySize), $sizeIds) !== []) {
                return false;
            }
        }

        return true;
    }
}

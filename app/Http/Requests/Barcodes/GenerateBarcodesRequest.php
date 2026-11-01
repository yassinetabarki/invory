<?php

declare(strict_types=1);

namespace App\Http\Requests\Barcodes;

use App\Actions\Barcodes\GenerateBarcodes;
use App\Models\Product;
use App\Models\Size;
use App\Services\Products\SizeLists;
use Illuminate\Auth\Access\Response;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

/**
 * "Generate" on the Barcodes tab (INV-8-c, AC-3): the variants asked for, `{ variants: [{ colour_id, size_id }] }`. At least one and at
 * most the product's colours x sizes; each a colour of the product (which is a colour of its workshop: the colours come through the
 * product's workshop-scoped relation) and a size of its category's list; none twice. Every refusal, here or under the lock in
 * `GenerateBarcodes`, is the one sentence on `variants`: the window has nowhere to show a finer message.
 */
final class GenerateBarcodesRequest extends FormRequest
{
    /**
     * `{product}` is route-model bound (an unknown, soft-deleted or other workshop's product is a 404 before anything is validated),
     * then the product's `update` policy decides.
     */
    public function authorize(): Response
    {
        $product = $this->route('product');

        // The Gate's own response, so a policy's `denyAsNotFound()` stays a 404 (ADR-0013 decision 5).
        return $product instanceof Product ? Gate::inspect('update', $product) : Response::denyAsNotFound();
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $colourIds = $this->product()->colours()->pluck('colours.id')->map(fn (mixed $id): int => (int) $id)->all();
        $sizeIds = app(SizeLists::class)->category($this->product()->category_id)->sizeList->sizes->map(fn (Size $size): int => $size->id)->all();

        return [
            'variants' => ['required', 'array', 'min:1', 'max:'.(count($colourIds) * count($sizeIds))],
            'variants.*' => ['required', 'array'],
            'variants.*.colour_id' => ['required', 'integer', Rule::in($colourIds)],
            'variants.*.size_id' => ['required', 'integer', Rule::in($sizeIds)],
        ];
    }

    /** @return list<callable(Validator): void> */
    public function after(): array
    {
        return [function (Validator $validator): void {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $pairs = array_map(fn (array $variant): string => $variant['colour_id'].'-'.$variant['size_id'], $this->variants());

            if (count(array_unique($pairs)) !== count($pairs)) {
                $validator->errors()->add('variants', GenerateBarcodes::refusal());
            }
        }];
    }

    /**
     * The variants asked for, as whole numbers. Call it once the request has passed.
     *
     * @return list<array{colour_id: int, size_id: int}>
     */
    public function variants(): array
    {
        $variants = [];

        foreach ($this->array('variants') as $variant) {
            $variant = is_array($variant) ? $variant : [];
            $variants[] = [
                'colour_id' => is_scalar($variant['colour_id'] ?? null) ? (int) $variant['colour_id'] : 0,
                'size_id' => is_scalar($variant['size_id'] ?? null) ? (int) $variant['size_id'] : 0,
            ];
        }

        return $variants;
    }

    /** Whatever failed, the window gets the one sentence on `variants` (a message per field would have nowhere to show). */
    protected function failedValidation(Validator $validator): void
    {
        $errors = $validator->errors();

        foreach (array_keys($errors->messages()) as $key) {
            $errors->forget($key);
        }

        $errors->add('variants', GenerateBarcodes::refusal());

        parent::failedValidation($validator);
    }

    private function product(): Product
    {
        $product = $this->route('product');

        return $product instanceof Product ? $product : abort(404);
    }
}

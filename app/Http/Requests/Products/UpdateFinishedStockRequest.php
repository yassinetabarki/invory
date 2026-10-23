<?php

declare(strict_types=1);

namespace App\Http\Requests\Products;

use App\Actions\Products\UpdateFinishedStock;
use App\Models\Product;
use App\Models\Size;
use App\Services\Orders\OrderMessages;
use App\Services\Products\SizeLists;
use App\Services\Products\SizeNames;
use Illuminate\Auth\Access\Response;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

/**
 * "Update stock" (S-56-c): a whole number from 0 to 1,000,000 for every size of the product's list, `sizes` keyed by size id; an empty
 * field counts as 0. When the keys are not exactly the product's sizes (the sizes changed while the window was open) the only error is
 * the one on `sizes`. `UpdateFinishedStock` checks the sizes again under the product's row lock.
 */
final class UpdateFinishedStockRequest extends FormRequest
{
    /**
     * `{product}` is route-model bound (an unknown, soft-deleted or other workshop's product is a 404 before anything is validated),
     * then the policy decides.
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
        // Wrong keys: no field rules, so a foreign key gets no message of its own; `after()` reports the sizes.
        return $this->hasProductSizes() ? ['sizes.*' => ['bail', 'nullable', 'integer', 'min:0', 'max:1000000']] : [];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        $message = OrderMessages::sizeQuantity();
        $messages = [];

        // Each size of the product's list names itself (S-70-a). They come first: the validator takes the first message that matches,
        // and `sizes.*` also matches these keys. A key that is not a size of the list keeps the bare message.
        foreach ($this->productSizes() as $size) {
            $named = SizeNames::prefixed($size->label, $message);
            $messages["sizes.{$size->id}.integer"] = $messages["sizes.{$size->id}.min"] = $messages["sizes.{$size->id}.max"] = $named;
        }

        return $messages + ['sizes.*.integer' => $message, 'sizes.*.min' => $message, 'sizes.*.max' => $message];
    }

    /** @return list<callable(Validator): void> */
    public function after(): array
    {
        return [function (Validator $validator): void {
            if (! $this->hasProductSizes()) {
                $validator->errors()->add('sizes', UpdateFinishedStock::sizesChanged($this->product()->name));
            }
        }];
    }

    /**
     * The validated counts by size id, an empty field as 0. Call it once the request has passed.
     *
     * @return array<int, int>
     */
    public function unitsBySizeId(): array
    {
        $units = [];

        foreach ($this->array('sizes') as $sizeId => $value) {
            $units[(int) $sizeId] = is_scalar($value) ? (int) $value : 0;
        }

        return $units;
    }

    /** Whether `sizes` is an array keyed by exactly the size ids of the product's list. */
    private function hasProductSizes(): bool
    {
        $sizes = $this->input('sizes');

        return is_array($sizes) && UpdateFinishedStock::sameSizes(array_keys($sizes), $this->productSizes()->map(fn (Size $size): int => $size->id)->all());
    }

    /**
     * The sizes of the product's list, from the request's cached lists.
     *
     * @return Collection<int, Size>
     */
    private function productSizes(): Collection
    {
        return app(SizeLists::class)->category($this->product()->category_id)->sizeList->sizes;
    }

    private function product(): Product
    {
        $product = $this->route('product');

        return $product instanceof Product ? $product : abort(404);
    }
}

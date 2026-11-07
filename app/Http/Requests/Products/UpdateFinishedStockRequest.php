<?php

declare(strict_types=1);

namespace App\Http\Requests\Products;

use App\Actions\Products\UpdateFinishedStock;
use App\Models\Product;
use App\Models\Size;
use App\Services\Orders\OrderMessages;
use App\Services\Products\ProductSizes;
use App\Services\Products\SizeNames;
use Illuminate\Auth\Access\Response;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;

/**
 * "Update stock" (S-56-c, INV-2-a): a whole number from 0 to 1,000,000 for every size of the product's list in every colour of the
 * product, `stock` keyed by colour id then size id; an empty field counts as 0. When the keys are not exactly the product's colours
 * and sizes (they changed while the window was open) the only error is the one on `stock`. `UpdateFinishedStock` checks them again
 * under the product's row lock.
 */
final class UpdateFinishedStockRequest extends FormRequest
{
    /** @var list<int>|null The ids of the product's colours, read once. */
    private ?array $colourIds = null;

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
        // Wrong keys: no field rules, so a foreign key gets no message of its own; `after()` reports the stock.
        return $this->hasProductVariants() ? ['stock.*.*' => ['bail', 'nullable', 'integer', 'min:0', 'max:1000000']] : [];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        $message = OrderMessages::sizeQuantity();
        $messages = [];

        // Each size of the product's list names itself (S-70-a), in every colour. They come first: the validator takes the first
        // message that matches, and `stock.*.*` also matches these keys.
        foreach ($this->colourIds() as $colourId) {
            foreach ($this->productSizes() as $size) {
                $named = SizeNames::prefixed($size->label, $message);
                $messages["stock.{$colourId}.{$size->id}.integer"] = $messages["stock.{$colourId}.{$size->id}.min"] = $messages["stock.{$colourId}.{$size->id}.max"] = $named;
            }
        }

        return $messages + ['stock.*.*.integer' => $message, 'stock.*.*.min' => $message, 'stock.*.*.max' => $message];
    }

    /** @return list<callable(Validator): void> */
    public function after(): array
    {
        return [function (Validator $validator): void {
            if (! $this->hasProductVariants()) {
                $validator->errors()->add('stock', UpdateFinishedStock::stockChanged($this->product()->name));
            }
        }];
    }

    /**
     * The validated counts by colour id then size id, an empty field as 0. Call it once the request has passed.
     *
     * @return array<int, array<int, int>>
     */
    public function unitsByColourAndSize(): array
    {
        $units = [];

        foreach ($this->array('stock') as $colourId => $bySize) {
            foreach (is_array($bySize) ? $bySize : [] as $sizeId => $value) {
                $units[(int) $colourId][(int) $sizeId] = is_scalar($value) ? (int) $value : 0;
            }
        }

        return $units;
    }

    /** Whether `stock` is an array keyed by exactly the colour ids of the product, each holding exactly the size ids of its list. */
    private function hasProductVariants(): bool
    {
        $stock = $this->input('stock');

        return is_array($stock) && UpdateFinishedStock::matches($stock, $this->colourIds(), $this->productSizes()->map(fn (Size $size): int => $size->id)->all());
    }

    /**
     * The ids of the product's colours.
     *
     * @return list<int>
     */
    private function colourIds(): array
    {
        return $this->colourIds ??= array_values($this->product()->colours()->pluck('colours.id')->map(fn (mixed $id): int => (int) $id)->all());
    }

    /**
     * The sizes of the product, from the request's cached read.
     *
     * @return Collection<int, Size>
     */
    private function productSizes(): Collection
    {
        return app(ProductSizes::class)->of($this->product())->sizes;
    }

    private function product(): Product
    {
        $product = $this->route('product');

        return $product instanceof Product ? $product : abort(404);
    }
}

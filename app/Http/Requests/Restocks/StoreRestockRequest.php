<?php

declare(strict_types=1);

namespace App\Http\Requests\Restocks;

use App\Models\Colour;
use App\Models\Product;
use App\Models\ProductRestock;
use App\Models\Size;
use App\Models\SizeList;
use App\Services\Orders\OrderMessages;
use App\Services\Products\SizeLists;
use App\Services\Restocks\RestockMessages;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Http\FormRequest;

/**
 * New restock (INV-3, AC-1 and AC-3): the details, then 1 to 50 products, each with `units` by colour id then size id, every field
 * empty or a whole number from 0 to 1,000,000. A field's message starts with its variant ("Indigo M: …"); a product with nothing typed
 * is refused on `products.{i}`, a restock with nothing typed at all on `products`. Whether a product was deleted, or its colours or
 * sizes changed, and the 1,000,000 cap on the shelf are checked by `RecordRestock` under the products' locks.
 */
final class StoreRestockRequest extends FormRequest
{
    public const MAX_PRODUCTS = 50;

    /** @var Collection<int, Product>|null The products asked for, deleted ones included, by id. */
    private ?Collection $products = null;

    public function authorize(): bool
    {
        return $this->user()?->can('create', ProductRestock::class) ?? false;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'restocked_on' => ['bail', 'required', 'date_format:Y-m-d', 'before_or_equal:'.now()->toDateString()],
            'reference' => ['nullable', 'string', 'max:64'],
            'note' => ['nullable', 'string', 'max:255'],
            'from_product' => ['nullable', 'integer'],
            'products' => ['bail', 'required', 'array', 'list', 'max:'.self::MAX_PRODUCTS],
            'products.*' => ['array:product_id,units'],
            'products.*.product_id' => ['bail', 'required', 'integer', 'distinct'],
            'products.*.units' => ['present', 'array'],
            'products.*.units.*' => ['array'],
            'products.*.units.*.*' => ['bail', 'nullable', 'integer', 'min:0', 'max:1000000'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        $messages = [
            'restocked_on.required' => RestockMessages::dateRequired(),
            'restocked_on.date_format' => RestockMessages::dateRequired(),
            'restocked_on.before_or_equal' => RestockMessages::dateInFuture(),
            'reference.max' => RestockMessages::referenceTooLong(),
            'note.max' => RestockMessages::noteTooLong(),
            'products.required' => RestockMessages::nothingTyped(),
            'products.max' => RestockMessages::tooManyProducts(),
        ];

        // Each field names its variant. They come first: the validator takes the first message that matches.
        foreach ($this->lines() as $i => $line) {
            $product = $this->products()->get($line['product_id']);

            if ($product === null) {
                continue;
            }

            $sizeList = app(SizeLists::class)->category($product->category_id)->sizeList;

            foreach ($product->colours as $colour) {
                foreach ($sizeList->sizes as $size) {
                    $named = RestockMessages::wholeNumber(self::variant($colour, $size, $sizeList));

                    foreach (['integer', 'min', 'max'] as $rule) {
                        $messages["products.{$i}.units.{$colour->id}.{$size->id}.{$rule}"] = $named;
                    }
                }
            }
        }

        $any = OrderMessages::sizeQuantity();

        return $messages + ['products.*.units.*.*.integer' => $any, 'products.*.units.*.*.min' => $any, 'products.*.units.*.*.max' => $any];
    }

    /** @return list<callable(Validator): void> */
    public function after(): array
    {
        return [function (Validator $validator): void {
            if ($validator->errors()->has('products') || ! is_array($this->input('products'))) {
                return;
            }

            $anything = false;

            foreach ($this->lines() as $i => $line) {
                if ($validator->errors()->has("products.{$i}.*") || $validator->errors()->has("products.{$i}")) {
                    $anything = true; // a wrong field is reported on its own; don't add "nothing typed" on top of it

                    continue;
                }

                $product = $this->products()->get($line['product_id']);

                if ($product === null) {
                    $validator->errors()->add("products.{$i}.product", RestockMessages::unknownProduct());
                    $anything = true;

                    continue;
                }

                if ($line['total'] > 0) {
                    $anything = true;
                } elseif (! $product->trashed()) {
                    $validator->errors()->add("products.{$i}", RestockMessages::lineEmpty($product->name));
                }
            }

            if (! $anything && $validator->errors()->isEmpty()) {
                $validator->errors()->add('products', RestockMessages::nothingTyped());
            }
        }];
    }

    /**
     * The validated restock, empty fields as 0. Call it once the request has passed.
     *
     * @return array{restocked_on: string, reference: ?string, note: ?string, products: list<array{product_id: int, units: array<int, array<int, int>>}>}
     */
    public function restock(): array
    {
        $reference = trim((string) $this->input('reference'));
        $note = trim((string) $this->input('note'));

        return [
            'restocked_on' => (string) $this->input('restocked_on'),
            'reference' => $reference === '' ? null : $reference,
            'note' => $note === '' ? null : $note,
            'products' => array_map(fn (array $line): array => ['product_id' => $line['product_id'], 'units' => $line['units']], $this->lines()),
        ];
    }

    /** The product the restock was started from (its page), if any. */
    public function fromProduct(): ?int
    {
        $id = $this->input('from_product');

        return is_numeric($id) ? (int) $id : null;
    }

    /**
     * The products as sent, each field as a whole number (empty, or not a number yet, as 0) and the line's total.
     *
     * @return list<array{product_id: int, units: array<int, array<int, int>>, total: int}>
     */
    private function lines(): array
    {
        $lines = [];

        foreach (array_values(array_filter((array) $this->input('products', []), 'is_array')) as $line) {
            $units = [];
            $total = 0;

            foreach (is_array($line['units'] ?? null) ? $line['units'] : [] as $colourId => $bySize) {
                $units[(int) $colourId] = [];

                foreach (is_array($bySize) ? $bySize : [] as $sizeId => $value) {
                    $count = is_numeric($value) && (int) $value == $value ? max(0, (int) $value) : 0;
                    $units[(int) $colourId][(int) $sizeId] = $count;
                    $total += $count;
                }
            }

            $lines[] = ['product_id' => is_numeric($line['product_id'] ?? null) ? (int) $line['product_id'] : 0, 'units' => $units, 'total' => $total];
        }

        return $lines;
    }

    /**
     * The products asked for, of the current workshop (the scope), deleted ones included, with their colours: one query each.
     *
     * @return Collection<int, Product>
     */
    private function products(): Collection
    {
        return $this->products ??= Product::withTrashed()->with('colours')
            ->whereKey(array_column($this->lines(), 'product_id'))->get()->keyBy('id');
    }

    private static function variant(Colour $colour, Size $size, SizeList $sizeList): string
    {
        return RestockMessages::variant($colour->name, $size->label, $sizeList->code === SizeList::ONE_SIZE);
    }
}

<?php

declare(strict_types=1);

namespace App\Http\Requests\Orders;

use App\Actions\Orders\SyncOrderLines;
use App\Enums\OrderLineSizeMode;
use App\Enums\ProductStatus;
use App\Models\Order;
use App\Models\OrderLine;
use App\Models\Product;
use App\Services\Orders\OrderMessages;
use Closure;
use Illuminate\Auth\Access\Response;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

/**
 * Record an order (S-48-c). {@see UpdateOrderRequest} reuses every rule, with the products already on the order.
 */
class StoreOrderRequest extends FormRequest
{
    /** An order holds up to this many products. */
    private const MAX_LINES = 50;

    /** Most ids resolved in one query: more lines than this are refused by the `max` rule anyway. */
    private const MAX_RESOLVED_IDS = 100;

    /** A line holds up to this many units, whatever the sizes. */
    private const MAX_UNITS = 1000000;

    /** @var Collection<int, Product>|null The submitted products by id, loaded once per request. */
    private ?Collection $submittedProducts = null;

    /** @var array<int, int|string>|null The first line each submitted product appears on, by product id. */
    private ?array $firstLineOf = null;

    public function authorize(): bool|Response
    {
        return $this->user()?->can('create', Order::class) ?? false;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'client' => ['required', 'string', 'max:100'],
            'ordered_on' => ['bail', 'required', 'date_format:Y-m-d', 'before_or_equal:today'],
            'note' => ['nullable', 'string', 'max:1000'],
            'lines' => ['bail', 'required', 'array', 'min:1', 'max:'.self::MAX_LINES],
            'lines.*.product_id' => ['bail', 'required', $this->orderableProduct()],
            // The values of a line depend on how it counts, so `after()` checks them. These rules only keep the fields in `validated()`.
            'lines.*.quantity' => ['nullable'],
            'lines.*.sizes' => ['nullable'],
            'lines.*.sizes.*' => ['nullable'],
        ];
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return ['ordered_on' => 'order date', 'lines.*.product_id' => 'product'];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'ordered_on.before_or_equal' => __("The order date can't be in the future."),
            'lines.required' => __('Add at least one product.'),
            'lines.min' => __('Add at least one product.'),
            'lines.max' => __('An order can hold up to :max products.', ['max' => self::MAX_LINES]),
            'lines.*.product_id.required' => __('Choose a product.'),
        ];
    }

    /**
     * The quantity of every line, once the lines are known: a single `quantity` for a line that counts as one size, a quantity per size
     * (`sizes`, size id → units) for a line counted by size. The other key of a line is ignored.
     *
     * @return list<callable(Validator): void>
     */
    public function after(): array
    {
        return [function (Validator $validator): void {
            foreach ($this->submittedLines() as $key => $line) {
                $id = is_array($line) ? self::productId($line['product_id'] ?? null) : null;
                $product = $id === null ? null : $this->submittedProducts()->get($id);

                // A line without a valid product is refused on its product already.
                if ($product !== null) {
                    $this->checkUnits($validator, "lines.{$key}", $line, $product);
                }
            }
        }];
    }

    /**
     * The order's saved line of this product, with its `product` loaded: it decides how the line counts. None for a new order.
     */
    protected function savedLine(int $productId): ?OrderLine
    {
        return null;
    }

    /**
     * The products this order already has: they stay valid when no longer active or deleted. None for a new order.
     *
     * @return array<int, int>
     */
    protected function currentProductIds(): array
    {
        return [];
    }

    /**
     * The product exists in the workshop (a deleted one only when it is already on the order), is active (or already on the order),
     * and is not on an earlier line. Every message goes on the line it is about: the later line of a repeated product.
     */
    private function orderableProduct(): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail): void {
            $id = self::productId($value);
            $product = $id === null ? null : $this->submittedProducts()->get($id);
            $current = $id !== null && in_array($id, $this->currentProductIds(), true);

            if ($product === null || ($product->trashed() && ! $current)) {
                $fail('validation.exists')->translate();
            } elseif ($product->status !== ProductStatus::Active && ! $current) {
                $fail(__('Only active products can be ordered.'));
            } elseif ($this->isRepeated($attribute, $product->id)) {
                $fail(__(':product is already on this order.', ['product' => $product->name]));
            }
        };
    }

    /** True when an earlier line of the submitted order has this product: the line named by `$attribute` is then the repeat. */
    private function isRepeated(string $attribute, int $productId): bool
    {
        if ($this->firstLineOf === null) {
            $this->firstLineOf = [];

            foreach ($this->submittedLines() as $key => $line) {
                $id = self::productId(is_array($line) ? ($line['product_id'] ?? null) : null);

                if ($id !== null) {
                    $this->firstLineOf[$id] ??= $key;
                }
            }
        }

        return isset($this->firstLineOf[$productId]) && (string) $this->firstLineOf[$productId] !== explode('.', $attribute)[1];
    }

    /**
     * Every submitted product in one query (an order can hold 50 lines), soft-deleted ones included. The workshop scope hides
     * another workshop's products: they are simply not found.
     *
     * @return Collection<int, Product>
     */
    private function submittedProducts(): Collection
    {
        if ($this->submittedProducts === null) {
            $ids = [];

            foreach ($this->submittedLines() as $line) {
                $id = self::productId(is_array($line) ? ($line['product_id'] ?? null) : null);

                if ($id !== null) {
                    $ids[$id] = $id;
                }
            }

            $this->submittedProducts = $ids === []
                ? new Collection
                : Product::query()->withTrashed()->whereKey(array_slice(array_values($ids), 0, self::MAX_RESOLVED_IDS))
                    ->get(['id', 'name', 'status', 'category_id', 'deleted_at'])->keyBy('id');
        }

        return $this->submittedProducts;
    }

    /**
     * The units of one line. A line that counts as one size (a "One size" product, or recorded before sizes) needs its `quantity`. A
     * line counted by size needs `sizes`: every size of its list empty or a whole number from 0 to 1,000,000, a size of another list
     * refused (the form was built for other sizes), then the line's total from 1 to 1,000,000.
     *
     * @param  array<string, mixed>  $line
     */
    private function checkUnits(Validator $validator, string $field, array $line, Product $product): void
    {
        [$mode, $list] = app(SyncOrderLines::class)->counting($product, $this->savedLine($product->id));

        if ($mode !== OrderLineSizeMode::BySize) {
            if (! self::wholeNumber($line['quantity'] ?? null, 1)) {
                $validator->errors()->add("{$field}.quantity", __('The quantity must be a whole number from 1 to 1,000,000.'));
            }

            return;
        }

        $ids = $list->sizes->pluck('id')->all();
        $total = 0;
        $foreign = false;
        $failed = false;

        foreach (is_array($line['sizes'] ?? null) ? $line['sizes'] : [] as $id => $units) {
            if (! in_array($id, $ids, true)) {
                $foreign = true;
            } elseif ($units !== null && $units !== '') {
                if (self::wholeNumber($units, 0)) {
                    $total += (int) $units;
                } else {
                    $failed = true;
                    $validator->errors()->add("{$field}.sizes.{$id}", OrderMessages::sizeQuantity());
                }
            }
        }

        if ($foreign) {
            $validator->errors()->add("{$field}.sizes", OrderMessages::orderSizesChanged($product->name));
        } elseif (! $failed && $total < 1) {
            $validator->errors()->add("{$field}.sizes", OrderMessages::atLeastOneUnit($product->name));
        } elseif (! $failed && $total > self::MAX_UNITS) {
            $validator->errors()->add("{$field}.sizes", OrderMessages::lineTooBig());
        }
    }

    /** A whole number (an int, or its digits as typed) from `$min` to 1,000,000. */
    private static function wholeNumber(mixed $value, int $min): bool
    {
        $number = is_int($value) || is_string($value) ? filter_var($value, FILTER_VALIDATE_INT) : false;

        return $number !== false && $number >= $min && $number <= self::MAX_UNITS;
    }

    /** @return array<int|string, mixed> */
    private function submittedLines(): array
    {
        $lines = $this->input('lines');

        return is_array($lines) ? $lines : [];
    }

    /** A product id as sent: a whole number (an int, or up to 18 digits). Anything else never reaches the database. */
    private static function productId(mixed $value): ?int
    {
        if (is_int($value)) {
            return $value > 0 ? $value : null;
        }

        return is_string($value) && preg_match('/^[0-9]{1,18}$/', $value) === 1 && (int) $value > 0 ? (int) $value : null;
    }
}

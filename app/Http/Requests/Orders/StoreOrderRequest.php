<?php

declare(strict_types=1);

namespace App\Http\Requests\Orders;

use App\Enums\ProductStatus;
use App\Models\Order;
use App\Models\Product;
use Closure;
use Illuminate\Auth\Access\Response;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Record an order (S-48-c). {@see UpdateOrderRequest} reuses every rule, with the products already on the order.
 */
class StoreOrderRequest extends FormRequest
{
    /** An order holds up to this many products. */
    private const MAX_LINES = 50;

    /** Most ids resolved in one query: more lines than this are refused by the `max` rule anyway. */
    private const MAX_RESOLVED_IDS = 100;

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
            'lines.*.quantity' => ['bail', 'required', 'integer', 'min:1', 'max:1000000'],
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
        $quantity = __('The quantity must be a whole number from 1 to 1,000,000.');

        return [
            'ordered_on.before_or_equal' => __("The order date can't be in the future."),
            'lines.required' => __('Add at least one product.'),
            'lines.min' => __('Add at least one product.'),
            'lines.max' => __('An order can hold up to :max products.', ['max' => self::MAX_LINES]),
            'lines.*.product_id.required' => __('Choose a product.'),
            'lines.*.quantity.required' => $quantity,
            'lines.*.quantity.integer' => $quantity,
            'lines.*.quantity.min' => $quantity,
            'lines.*.quantity.max' => $quantity,
        ];
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
                    ->get(['id', 'name', 'status', 'deleted_at'])->keyBy('id');
        }

        return $this->submittedProducts;
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

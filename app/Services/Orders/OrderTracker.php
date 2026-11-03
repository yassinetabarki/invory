<?php

declare(strict_types=1);

namespace App\Services\Orders;

use App\Enums\OrderLineSizeMode;
use App\Models\Order;
use App\Models\OrderLine;
use App\Models\OrderLineSize;
use App\Services\Products\FinishedStock;

/**
 * The Order tracker of the order page (INV-14, AC-1 and AC-2; ADR-0026): the order's counts and status, and per line its rows by colour
 * (list order) then size, each with To deliver, Delivered, Cancelled, Total and the shelf now, plus what each window may move. Every
 * figure is added up here, never on the page. The shelf of every product of the order is read in one query (`FinishedStock::ofMany`).
 *
 * The order must be loaded by `OrderBook::load()` (its lines with their product, sizing count and rows with their colour and size).
 */
final readonly class OrderTracker
{
    public function __construct(private FinishedStock $finishedStock, private OrderLineSizing $sizing) {}

    /**
     * @return array<string, mixed> the `OrderTracker` shape of resources/js/types/domain.ts
     */
    public function of(Order $order): array
    {
        $shelf = $this->shelfOf($order);
        $lines = [];

        foreach ($order->lines as $line) {
            $lines[] = $this->line($line, $shelf[$line->product_id] ?? []);
        }

        $progress = $order->progress();

        return [
            ...self::counts($order->unitsToDeliver(), $order->unitsDelivered(), $order->unitsCancelled()),
            'status' => $progress->value,
            'status_label' => $progress->label(),
            'lines' => $lines,
        ];
    }

    /**
     * @param  array<int, array<int, int>>  $shelf  colour id → size id → units on the shelf now
     * @return array<string, mixed>
     */
    private function line(OrderLine $line, array $shelf): array
    {
        $oneField = $this->sizing->forLine($line) !== OrderLineSizeMode::BySize;
        $colours = [];

        foreach ($line->sizes as $row) {
            $colours[$row->colour_id] ??= ['colour' => self::colour($row), 'rows' => []];
            $colours[$row->colour_id]['rows'][] = $this->row($row, $shelf, $oneField);
        }

        $progress = $line->progress();

        return [
            'id' => $line->id,
            'product' => [
                'id' => $line->product->id, 'sku' => $line->product->sku, 'name' => $line->product->name,
                'status' => $line->product->status->value, 'deleted' => $line->product->trashed(),
            ],
            'one_size' => $oneField,
            ...self::counts($line->to_deliver, $line->delivered, $line->cancelled),
            'status' => $progress->value,
            'status_label' => $progress->label(),
            'colours' => array_values(array_map(static function (array $colour): array {
                $sum = static fn (string $key): int => array_sum(array_column($colour['rows'], $key));

                return [
                    'colour' => $colour['colour'],
                    ...self::counts($sum('to_deliver'), $sum('delivered'), $sum('cancelled')),
                    'rows' => $colour['rows'],
                ];
            }, $colours)),
        ];
    }

    /**
     * @param  array<int, array<int, int>>  $shelf
     * @return array<string, mixed>
     */
    private function row(OrderLineSize $row, array $shelf, bool $oneField): array
    {
        // A line recorded before sizes names "One size" for a product with sizes: no shelf row has that size, so it reads 0 and can't
        // be delivered until Edit order gives it real sizes (ADR-0026).
        $onShelf = $shelf[$row->colour_id][$row->size_id] ?? 0;

        return [
            'size' => ['id' => $row->size_id, 'label' => (string) $row->getAttribute('size_label')],
            ...self::counts($row->to_deliver, $row->delivered, $row->cancelled),
            'on_shelf' => $onShelf,
            'can_deliver' => min($row->to_deliver, $onShelf),
        ];
    }

    /** @return array{to_deliver: int, delivered: int, cancelled: int, total: int} */
    private static function counts(int $toDeliver, int $delivered, int $cancelled): array
    {
        return ['to_deliver' => $toDeliver, 'delivered' => $delivered, 'cancelled' => $cancelled, 'total' => $toDeliver + $delivered + $cancelled];
    }

    /** @return array{id: int, code: string, name: string, swatch: string} */
    private static function colour(OrderLineSize $row): array
    {
        return [
            'id' => $row->colour_id,
            'code' => (string) $row->getAttribute('colour_code'),
            'name' => (string) $row->getAttribute('colour_name'),
            'swatch' => (string) $row->getAttribute('colour_swatch'),
        ];
    }

    /**
     * Product id → colour id → size id → units on the shelf now, for every product of the order, in one query.
     *
     * @return array<int, array<int, array<int, int>>>
     */
    private function shelfOf(Order $order): array
    {
        $products = $order->lines->map(fn (OrderLine $line) => $line->product)->unique('id')->values()->all();
        $shelf = [];

        foreach ($this->finishedStock->ofMany($products) as $productId => $level) {
            foreach ($level->colours as $entry) {
                foreach ($entry['sizes'] as $size) {
                    $shelf[$productId][$entry['colour']->id][$size['size']->id] = $size['units'];
                }
            }
        }

        return $shelf;
    }
}

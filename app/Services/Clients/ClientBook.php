<?php

declare(strict_types=1);

namespace App\Services\Clients;

use App\Domain\Clients\PhoneNumber;
use App\Models\Client;
use App\Models\Order;
use Illuminate\Database\Eloquent\Builder;

/**
 * The Eloquent side of the client pages and lookups (INV-4): the clients with their order figures, the cities already used, and a
 * client found by phone or by name. Read-only; the workshop scope limits every query to the current workshop.
 */
final readonly class ClientBook
{
    /**
     * Clients with the figures a `ClientSummary` reads: `orders_count` (her orders still shown), `open_orders_count` (the Orders
     * list's "open": a line with units still to ship) and `orders_max_ordered_on` (her last order date). One query with sub-selects.
     *
     * @return Builder<Client>
     */
    public function withFigures(): Builder
    {
        return Client::query()
            ->withCount(['orders', 'orders as open_orders_count' => self::openOnly(...)])
            ->withMax('orders', 'ordered_on');
    }

    /**
     * Her open orders only, for `open_orders_count`.
     *
     * @param  Builder<Order>  $orders
     */
    private static function openOnly(Builder $orders): void
    {
        $orders->open();
    }

    /** The client with this phone (compared on its key), with her figures; none for a phone Invory doesn't know. */
    public function byPhone(string $typed): ?Client
    {
        $phone = PhoneNumber::parse($typed);

        return $phone === null ? null : $this->withFigures()->where('phone_key', $phone->key)->first();
    }

    /** The first client (A → Z, then the oldest) named exactly so, ignoring capitals and the spaces around, with her figures. */
    public function byName(string $name): ?Client
    {
        $name = trim($name);

        return $name === '' ? null : $this->withFigures()
            ->whereRaw('lower(name) = ?', [mb_strtolower($name)])
            ->orderBy('name')->orderBy('id')
            ->first();
    }

    /**
     * The cities the workshop's clients already have, A → Z, each once (City suggests them).
     *
     * @return list<string>
     */
    public function cities(): array
    {
        return array_values(Client::query()->distinct()->orderBy('city')->pluck('city')->all());
    }

    /** A client with her figures by id, or none (an unknown id, or another workshop's client). */
    public function find(int $id): ?Client
    {
        return $this->withFigures()->whereKey($id)->first();
    }
}

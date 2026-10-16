<?php

declare(strict_types=1);

namespace App\Services\Orders;

/**
 * The sentences of the order pages (S-49), built in one place so the order's `delete_refusal` and the refusal of the delete itself
 * can't say different things. A count of 1 reads in the singular.
 */
final readonly class OrderMessages
{
    /**
     * Why an order can't be deleted: `$held` units (in cutting plus ready to ship) still hold material taken out of stock.
     */
    public static function deleteRefusal(string $client, int $held): string
    {
        return trans_choice(
            "{1} :client can't be deleted: :count unit is in cutting or ready to ship. Move them back to To start first."
            ."|[2,*] :client can't be deleted: :count units are in cutting or ready to ship. Move them back to To start first.",
            $held,
            ['client' => $client],
        );
    }
}

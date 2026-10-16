<?php

declare(strict_types=1);

namespace App\Services\Orders;

use App\Enums\OrderLineMove;

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

    /** The toast of a move: "30 units of Denim Jacket moved to In cutting." / "5 units of Denim Jacket moved back to To start." */
    public static function moved(OrderLineMove $move, int $units, string $product): string
    {
        $replace = ['product' => $product, 'stage' => $move->toStage()->label()];

        return $move->isBack()
            ? trans_choice(
                '{1} :count unit of :product moved back to :stage.|[2,*] :count units of :product moved back to :stage.',
                $units,
                $replace,
            )
            : trans_choice(
                '{1} :count unit of :product moved to :stage.|[2,*] :count units of :product moved to :stage.',
                $units,
                $replace,
            );
    }

    /** Why a move is refused: the stage holds `$held` units now (the saved count, which may differ from the screen's). */
    public static function moveLimit(int $held): string
    {
        return trans_choice('{1} You can move at most :count unit.|[0,*] You can move at most :count units.', $held);
    }

    /** Why a deleted product's units can't start cutting. */
    public static function productDeleted(string $product): string
    {
        return __(':product was deleted, so it can\'t be cut.', ['product' => $product]);
    }
}

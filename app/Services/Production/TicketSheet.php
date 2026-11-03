<?php

declare(strict_types=1);

namespace App\Services\Production;

use App\Domain\Barcodes\VariantSku;
use App\Models\ProductionBatch;
use Illuminate\Support\Collection;

/**
 * The work tickets of some batches (INV-15-b, AC-1): what each ticket prints. No client, no phone number and no price: a ticket lies
 * on the workshop floor. The units are what the batch holds now; the path is the phases as they are now, with the batch's step ticked.
 * Only batches still in production get a ticket; the others have left Progress.
 */
final readonly class TicketSheet
{
    public function __construct(private BatchNames $names, private TicketQr $qr) {}

    /**
     * @param  list<int>  $ids  in the order to print
     * @return list<array{id: int, number: string, product: string, variant: string, units: int, started_on: string, sku: string,
     *     split_from: string|null, path: list<string>, current: int, printed_on: string, qr: array{size: int, path: string}}>
     */
    public function tickets(array $ids): array
    {
        /** @var Collection<int, ProductionBatch> $batches */
        $batches = ProductionBatch::query()
            ->whereIn('id', $ids)
            ->inProduction()
            ->with(['product', 'colour', 'size', 'splitFrom'])
            ->get()
            ->keyBy('id');

        $path = ProductionPath::current();
        $names = [...array_column($path->steps, 'name'), ProductionMessages::PUT_IN_STOCK];
        $refs = array_column($path->steps, 'ref');
        $today = today()->toDateString();
        $tickets = [];

        foreach ($ids as $id) {
            $batch = $batches->get($id);

            if (! $batch instanceof ProductionBatch) {
                continue;
            }

            $oneSize = $this->names->isOneSize($batch);
            $current = array_search(ProductionPath::refOf($batch->phase_id), $refs, true);

            $tickets[] = [
                'id' => $batch->id,
                'number' => $batch->label,
                'product' => $batch->product->name,
                'variant' => $this->names->dotted($batch),
                'units' => $batch->units,
                'started_on' => $batch->started_on->toDateString(),
                'sku' => VariantSku::of($batch->product->sku, $batch->colour->code, $oneSize ? null : $batch->size->label),
                'split_from' => $batch->splitFrom?->label,
                'path' => $names,
                'current' => $current === false ? 0 : $current,
                'printed_on' => $today,
                'qr' => $this->qr->of($batch),
            ];
        }

        return $tickets;
    }
}

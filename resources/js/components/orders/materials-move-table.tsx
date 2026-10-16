import { TriangleAlert } from 'lucide-react';
import { useId } from 'react';
import { formatUnits } from '@/components/orders/stage-figure';
import type { PreviewState } from '@/components/orders/use-move-preview';
import { Quantity } from '@/components/shared/quantity';
import { Button } from '@/components/ui/button';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { useAppSettings } from '@/hooks/use-app-settings';
import { formatQuantity } from '@/lib/format';
import type { MaterialMoveRow, MovePreview } from '@/types/domain';

// Fixed UI copy for the two moves that have a table.
const COPY: Record<
    MovePreview['move'],
    { heading: string; amount: string; after: string }
> = {
    start_cutting: {
        heading: 'Materials taken out of stock',
        amount: 'Needed',
        after: 'Left after',
    },
    back_to_start: {
        heading: 'Materials returned to stock',
        amount: 'Returned',
        after: 'After return',
    },
};

const NOT_RETURNED: Record<
    NonNullable<MaterialMoveRow['not_returned']>,
    string
> = {
    deleted: 'Deleted, not returned',
    unit_changed: 'Unit changed, not returned',
};

/** The last cell of a row: the figure left (or returned to), or the words that say why there is none. */
function AfterCell({ row }: { row: MaterialMoveRow }) {
    const { locale } = useAppSettings();
    const unit = row.material.unit;

    if (row.short_by !== null) {
        return (
            <span className="inline-flex items-center gap-1.5 font-medium text-destructive-foreground">
                <TriangleAlert className="size-4 shrink-0" aria-hidden="true" />
                <span className="tabular-nums">
                    {`Short by ${formatQuantity(row.short_by, { locale, unit })}`}
                </span>
            </span>
        );
    }

    if (row.not_returned !== null) {
        return (
            <span className="text-muted-foreground">
                {NOT_RETURNED[row.not_returned]}
            </span>
        );
    }

    return <Quantity value={row.after} unit={unit} />;
}

type MaterialsTableProps = { preview: MovePreview; productName: string };

function MaterialsTable({ preview, productName }: MaterialsTableProps) {
    const headingId = useId();
    const { locale } = useAppSettings();
    const copy = COPY[preview.move];
    const starting = preview.move === 'start_cutting';

    if (starting && !preview.has_composition) {
        return (
            <p className="text-sm">
                {productName} has no composition, so no material is taken out of
                stock.
            </p>
        );
    }

    if (!starting && preview.nothing_taken) {
        return (
            <p className="text-sm">
                No material was taken out of stock for these units, so none is
                returned.
            </p>
        );
    }

    return (
        <section aria-labelledby={headingId} className="flex flex-col gap-2">
            <h3 id={headingId} className="text-sm font-semibold">
                {copy.heading}
            </h3>
            <div className="rounded-md border">
                <Table aria-labelledby={headingId}>
                    <TableHeader>
                        <TableRow>
                            <TableHead scope="col">Material</TableHead>
                            <TableHead scope="col">{copy.amount}</TableHead>
                            <TableHead scope="col">In stock</TableHead>
                            <TableHead scope="col">{copy.after}</TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        {preview.rows.map((row) => (
                            <TableRow key={row.material.id}>
                                <TableHead
                                    scope="row"
                                    className="font-medium whitespace-normal"
                                >
                                    {row.material.name}
                                </TableHead>
                                <TableCell>
                                    <Quantity
                                        value={row.quantity}
                                        unit={row.material.unit}
                                    />
                                </TableCell>
                                <TableCell>
                                    <Quantity
                                        value={row.in_stock}
                                        unit={row.material.unit}
                                    />
                                </TableCell>
                                <TableCell>
                                    <AfterCell row={row} />
                                </TableCell>
                            </TableRow>
                        ))}
                    </TableBody>
                </Table>
            </div>
            {starting && preview.max_cuttable !== null && (
                <p className="text-sm text-muted-foreground">
                    Waste included. Stock on hand allows{' '}
                    {formatUnits(preview.max_cuttable, locale)}.
                </p>
            )}
            {preview.short_message && (
                <p className="text-sm font-medium text-destructive-foreground">
                    {preview.short_message}
                </p>
            )}
        </section>
    );
}

type MaterialsMoveTableProps = {
    state: PreviewState;
    productName: string;
};

/**
 * The materials a move takes out of stock or returns to it, exactly as the
 * server sent them: "Loading materials…" until the answer for the number
 * typed arrives, a retry when it can't be had, then the table. No amount is
 * computed here.
 */
export function MaterialsMoveTable({
    state,
    productName,
}: MaterialsMoveTableProps) {
    switch (state.status) {
        case 'loading':
            return (
                <p className="text-sm text-muted-foreground">
                    Loading materials…
                </p>
            );
        case 'failed':
            return (
                <div
                    role="alert"
                    className="flex flex-col items-start gap-2 text-sm"
                >
                    <p>Couldn&apos;t load the materials for this move.</p>
                    <Button
                        type="button"
                        variant="outline"
                        size="sm"
                        onClick={state.retry}
                    >
                        Try again
                    </Button>
                </div>
            );
        case 'ready':
            return (
                <MaterialsTable
                    preview={state.preview}
                    productName={productName}
                />
            );
        default:
            // Idle (no number yet) shows nothing; a refused number shows under the field.
            return null;
    }
}

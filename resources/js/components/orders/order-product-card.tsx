import { Link } from '@inertiajs/react';
import { Ban, MoreHorizontal, Truck } from 'lucide-react';
import { Fragment, useId } from 'react';
import { DeliveryBar } from '@/components/orders/order-tracker';
import { ProgressStatus } from '@/components/orders/progress-status';
import { formatUnits } from '@/components/orders/stage-figure';
import { ColourSwatch } from '@/components/shared/colour-tag';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { useAppSettings } from '@/hooks/use-app-settings';
import { useIsMobile } from '@/hooks/use-mobile';
import { formatCount } from '@/lib/format';
import { cn } from '@/lib/utils';
import { show } from '@/routes/products';
import type { TrackerCounts, TrackerLine, TrackerRow } from '@/types/domain';

/** What a product card's buttons open (INV-14): Mark delivered, its move back, Cancel units. */
export type UnitsMode = 'deliver' | 'move_back' | 'cancel';

const COLUMNS = [
    { key: 'to_deliver', label: 'To deliver' },
    { key: 'delivered', label: 'Delivered' },
    { key: 'cancelled', label: 'Cancelled' },
    { key: 'total', label: 'Total' },
] as const;

const OUT = 'font-semibold text-amber-700 dark:text-amber-400';

/** What Mark delivered could take on the whole line: the server's `can_deliver` of each row, added up. */
export function deliverableUnits(line: TrackerLine): number {
    return line.colours.reduce(
        (sum, colour) =>
            sum + colour.rows.reduce((rows, row) => rows + row.can_deliver, 0),
        0,
    );
}

type OrderProductCardProps = {
    line: TrackerLine;
    onOpen: (line: TrackerLine, mode: UnitsMode) => void;
};

/**
 * One product of the order (INV-14, AC-2): its name, SKU, units, status and "5 of 50 delivered", then the table by colour and size with
 * To deliver, Delivered, Cancelled, Total and On the shelf now, and the buttons Mark delivered, Cancel units and "⋯". A button that can't
 * be pressed stays reachable and says why beside it. Every number is the server's; the screen only formats.
 */
export function OrderProductCard({ line, onOpen }: OrderProductCardProps) {
    const { locale } = useAppSettings();
    const mobile = useIsMobile();
    const id = useId();
    const name = line.product.name;
    const deliverable = deliverableUnits(line);
    const deliverReason =
        line.to_deliver === 0
            ? 'Nothing is left to deliver.'
            : deliverable === 0
              ? 'Nothing on the shelf covers this order yet. Units arrive from Progress or a restock.'
              : null;
    const cancelReason =
        line.to_deliver === 0
            ? 'Nothing to cancel: no unit is waiting to be delivered.'
            : null;
    const caption = `${name} by colour and size`;
    const figure = (value: number) => (
        <span
            className={cn(
                'font-mono tabular-nums',
                value === 0 && 'text-muted-foreground',
            )}
        >
            {formatCount(value, locale)}
        </span>
    );
    const shelf = (row: TrackerRow) =>
        row.on_shelf === 0 ? (
            <span className={OUT}>Out</span>
        ) : (
            figure(row.on_shelf)
        );
    const rowName = (row: TrackerRow, colour: string) =>
        line.one_size ? colour : row.size.label;

    const table = (
        <div
            role="region"
            aria-label={caption}
            tabIndex={0}
            className="overflow-x-auto rounded-md outline-none focus-visible:ring-[3px] focus-visible:ring-ring"
        >
            <table className="w-full min-w-max border-collapse text-sm">
                <caption className="sr-only">{caption}</caption>
                <thead>
                    <tr className="border-b text-muted-foreground">
                        <th
                            scope="col"
                            className="py-2 pr-3 text-left font-medium"
                        >
                            Colour · size
                        </th>
                        {COLUMNS.map(({ key, label }) => (
                            <th
                                key={key}
                                scope="col"
                                className="px-3 py-2 text-right font-medium"
                            >
                                {label}
                            </th>
                        ))}
                        <th scope="col" className="w-6" aria-hidden="true" />
                        <th
                            scope="col"
                            className="py-2 pl-3 text-right font-medium"
                        >
                            On the shelf now
                        </th>
                    </tr>
                </thead>
                <tbody>
                    {line.colours.map((colour) => (
                        <Fragment key={colour.colour.id}>
                            {!line.one_size && (
                                <tr className="bg-muted/50 font-bold">
                                    <th
                                        scope="row"
                                        className="py-2 pr-3 text-left"
                                    >
                                        <span className="inline-flex items-center gap-2">
                                            <ColourSwatch
                                                swatch={colour.colour.swatch}
                                            />
                                            {colour.colour.name}
                                        </span>
                                    </th>
                                    {COLUMNS.map(({ key }) => (
                                        <td
                                            key={key}
                                            className="px-3 py-2 text-right"
                                        >
                                            {figure(colour[key])}
                                        </td>
                                    ))}
                                    <td />
                                    <td />
                                </tr>
                            )}
                            {colour.rows.map((row) => (
                                <tr key={row.size.id} className="border-b">
                                    <th
                                        scope="row"
                                        className={cn(
                                            'py-2 pr-3 text-left font-normal',
                                            !line.one_size && 'pl-6',
                                        )}
                                    >
                                        {line.one_size ? (
                                            <span className="inline-flex items-center gap-2 font-bold">
                                                <ColourSwatch
                                                    swatch={
                                                        colour.colour.swatch
                                                    }
                                                />
                                                {colour.colour.name}
                                            </span>
                                        ) : (
                                            row.size.label
                                        )}
                                    </th>
                                    {COLUMNS.map(({ key }) => (
                                        <td
                                            key={key}
                                            className="px-3 py-2 text-right"
                                        >
                                            {figure(row[key])}
                                        </td>
                                    ))}
                                    <td />
                                    <td className="py-2 pl-3 text-right">
                                        {shelf(row)}
                                    </td>
                                </tr>
                            ))}
                        </Fragment>
                    ))}
                    <tr className="border-t-2 font-bold">
                        <th scope="row" className="py-2 pr-3 text-left">
                            Total
                        </th>
                        {COLUMNS.map(({ key }) => (
                            <td key={key} className="px-3 py-2 text-right">
                                {figure(line[key])}
                            </td>
                        ))}
                        <td />
                        <td />
                    </tr>
                </tbody>
            </table>
        </div>
    );

    /** "M · To deliver 20 · Total 20 · On the shelf Out": only the counts that hold units, and the shelf. */
    const phoneLine = (
        label: string,
        counts: TrackerCounts,
        onShelf: number | null,
    ) =>
        [
            label,
            ...COLUMNS.filter(({ key }) => counts[key] > 0).map(
                ({ key, label: caption }) =>
                    `${caption} ${formatCount(counts[key], locale)}`,
            ),
            ...(onShelf === null
                ? []
                : [
                      `On the shelf ${onShelf === 0 ? 'Out' : formatCount(onShelf, locale)}`,
                  ]),
        ].join(' · ');

    const phone = (
        <div className="flex flex-col gap-2" aria-label={caption} role="group">
            <p className="text-sm font-bold tabular-nums">
                {phoneLine('Total', line, null)}
            </p>
            {line.colours.map((colour) => (
                <div key={colour.colour.id} className="flex flex-col gap-1">
                    <p className="flex items-center gap-2 rounded-md bg-muted px-2 py-1.5 text-sm font-bold">
                        <ColourSwatch swatch={colour.colour.swatch} />
                        {line.one_size
                            ? phoneLine(
                                  colour.colour.name,
                                  colour.rows[0],
                                  colour.rows[0].on_shelf,
                              )
                            : colour.colour.name}
                    </p>
                    {!line.one_size &&
                        colour.rows.map((row) => (
                            <p
                                key={row.size.id}
                                className="px-2 text-sm tabular-nums"
                            >
                                {phoneLine(
                                    rowName(row, colour.colour.name),
                                    row,
                                    row.on_shelf,
                                )}
                            </p>
                        ))}
                </div>
            ))}
        </div>
    );

    const deliverReasonId = `${id}-deliver-reason`;
    const cancelReasonId = `${id}-cancel-reason`;

    return (
        <section
            aria-labelledby={`${id}-title`}
            className="flex min-w-0 flex-col gap-3 rounded-xl border bg-card p-4 text-card-foreground"
        >
            <div className="flex flex-wrap items-center gap-x-3 gap-y-1">
                <h3
                    id={`${id}-title`}
                    className="font-semibold [overflow-wrap:anywhere]"
                >
                    {line.product.deleted ? (
                        name
                    ) : (
                        <Link
                            href={show(line.product.id)}
                            className="hover:underline focus-visible:underline"
                        >
                            {name}
                        </Link>
                    )}
                </h3>
                <span className="font-mono text-sm text-muted-foreground">
                    {line.product.sku}
                </span>
                <span className="text-sm text-muted-foreground">
                    {formatUnits(line.total, locale)}
                </span>
                <ProgressStatus
                    status={line.status}
                    label={line.status_label}
                />
                <span className="text-sm font-medium tabular-nums">
                    {formatCount(line.delivered, locale)} of{' '}
                    {formatCount(line.total, locale)} delivered
                </span>
            </div>
            <DeliveryBar counts={line} className="h-1.5" />

            {mobile ? phone : table}

            <div className="flex flex-wrap items-start gap-2 max-sm:flex-col max-sm:items-stretch">
                <div className="flex flex-col gap-1">
                    <Button
                        type="button"
                        variant={deliverReason === null ? 'default' : 'outline'}
                        className="aria-disabled:opacity-50 max-sm:h-11"
                        aria-disabled={
                            deliverReason === null ? undefined : 'true'
                        }
                        aria-describedby={
                            deliverReason === null ? undefined : deliverReasonId
                        }
                        onClick={() =>
                            deliverReason === null && onOpen(line, 'deliver')
                        }
                    >
                        <Truck aria-hidden="true" />
                        Mark delivered
                    </Button>
                    {deliverReason && (
                        <p
                            id={deliverReasonId}
                            className="max-w-64 text-xs text-muted-foreground"
                        >
                            {deliverReason}
                        </p>
                    )}
                </div>
                <div className="flex flex-col gap-1">
                    <Button
                        type="button"
                        variant="outline"
                        className="text-destructive-foreground hover:text-destructive-foreground aria-disabled:opacity-50 max-sm:h-11"
                        aria-disabled={
                            cancelReason === null ? undefined : 'true'
                        }
                        aria-describedby={
                            cancelReason === null ? undefined : cancelReasonId
                        }
                        onClick={() =>
                            cancelReason === null && onOpen(line, 'cancel')
                        }
                    >
                        <Ban aria-hidden="true" />
                        Cancel units
                    </Button>
                    {cancelReason && (
                        <p
                            id={cancelReasonId}
                            className="max-w-64 text-xs text-muted-foreground"
                        >
                            {cancelReason}
                        </p>
                    )}
                </div>
                <DropdownMenu>
                    <DropdownMenuTrigger asChild>
                        <Button
                            type="button"
                            variant="outline"
                            size={mobile ? 'default' : 'icon'}
                            aria-label={`More actions for ${name}`}
                            className="max-sm:h-11"
                        >
                            <MoreHorizontal aria-hidden="true" />
                            {mobile && 'More'}
                        </Button>
                    </DropdownMenuTrigger>
                    <DropdownMenuContent align="start">
                        <DropdownMenuItem
                            disabled={line.delivered === 0}
                            onSelect={() => onOpen(line, 'move_back')}
                        >
                            Move delivered back to To deliver
                        </DropdownMenuItem>
                    </DropdownMenuContent>
                </DropdownMenu>
            </div>
        </section>
    );
}

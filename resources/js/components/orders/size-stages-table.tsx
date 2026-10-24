import { Fragment, useId } from 'react';
import type { ReactNode } from 'react';
import {
    formatUnits,
    STAGE_BOXES,
    StageFigure,
} from '@/components/orders/stage-figure';
import { useAppSettings } from '@/hooks/use-app-settings';
import { formatCount } from '@/lib/format';
import { cn } from '@/lib/utils';

// The table and the button rows under it share one column template and one cell padding, so a
// button ends where the figures above it end. Four stage columns; the Size column is 6rem wide.
const SIZE_COLUMN = 'w-24';
const ROW_COLUMNS = 'grid-cols-[6rem_repeat(4,minmax(0,1fr))]';
const CELL_X = 'px-3';
const BUTTON_LINES = ['forward', 'back'] as const;

export type SizeStagesColumn = {
    /** Key of this column's figure in each row and in `total`. */
    key: string;
    /** The column's caption ("To start", "In cutting"…). */
    caption: string;
    /** Id of the column heading, so a "Move back" can be described by it. */
    captionId: string;
    /** The button that moves units on from this stage (first row under the table). */
    forward?: ReactNode;
    /** The button that moves units back (second row under the table). */
    back?: ReactNode;
};

export type SizeStagesRow = {
    id: number;
    label: string;
    /** The server's whole-unit figures by column key; shown as they are. */
    figures: Record<string, number>;
};

type SizeStagesTableProps = {
    /** Names the table for screen readers: "Denim Jacket: units by size and stage". */
    productName: string;
    columns: SizeStagesColumn[];
    rows: SizeStagesRow[];
    /** The line's own figures: the Total row. */
    total: Record<string, number>;
};

/**
 * Each size of an order line against the stages, with the Total row last on a
 * wide screen (table, from 1024 px) and first on a phone (cards). It only shows the figures
 * it is given; the buttons come in as each column's `forward` and `back`, in two rows under the
 * table (outside it, so a screen reader hears the sizes and the Total only).
 */
export function SizeStagesTable({
    productName,
    columns,
    rows,
    total,
}: SizeStagesTableProps) {
    const { locale } = useAppSettings();
    const totalId = useId();

    return (
        <>
            <div className="hidden lg:block">
                <table className="w-full table-fixed text-sm">
                    <caption className="sr-only">
                        {productName}: units by size and stage
                    </caption>
                    <colgroup>
                        <col className={SIZE_COLUMN} />
                        {columns.map(({ key }) => (
                            <col key={key} />
                        ))}
                    </colgroup>
                    <thead>
                        <tr className="border-b text-left text-muted-foreground">
                            <th scope="col" className="py-2 pr-3 font-medium">
                                Size
                            </th>
                            {columns.map(({ key, caption, captionId }) => (
                                <th
                                    key={key}
                                    id={captionId}
                                    scope="col"
                                    className={cn(
                                        CELL_X,
                                        'py-2 text-right font-medium',
                                    )}
                                >
                                    {caption}
                                </th>
                            ))}
                        </tr>
                    </thead>
                    <tbody>
                        {rows.map(({ id, label, figures }) => (
                            <tr key={id} className="border-b">
                                <th
                                    scope="row"
                                    className="py-2 pr-3 text-left font-medium"
                                >
                                    {label}
                                </th>
                                {columns.map(({ key }) => (
                                    <td
                                        key={key}
                                        className={cn(
                                            CELL_X,
                                            'py-2 text-right tabular-nums',
                                        )}
                                    >
                                        {formatCount(figures[key], locale)}
                                    </td>
                                ))}
                            </tr>
                        ))}
                        <tr className="font-bold">
                            <th scope="row" className="py-2 pr-3 text-left">
                                Total
                            </th>
                            {columns.map(({ key }) => (
                                <td
                                    key={key}
                                    className={cn(
                                        CELL_X,
                                        'py-2 text-right tabular-nums',
                                    )}
                                >
                                    {formatCount(total[key], locale)}
                                </td>
                            ))}
                        </tr>
                    </tbody>
                </table>
                <div className={cn('mt-2 grid gap-y-2', ROW_COLUMNS)}>
                    {BUTTON_LINES.map((line) => (
                        <Fragment key={line}>
                            <div data-line={line} />
                            {columns.map((column) => (
                                <div
                                    key={column.key}
                                    data-line={line}
                                    className={cn(CELL_X, 'flex justify-end')}
                                >
                                    {column[line]}
                                </div>
                            ))}
                        </Fragment>
                    ))}
                </div>
            </div>

            <div className="flex flex-col gap-3 lg:hidden">
                <p id={totalId} className="text-sm font-bold">
                    Total
                </p>
                <ol aria-labelledby={totalId} className={STAGE_BOXES}>
                    {columns.map(({ key, caption, forward, back }) => (
                        <StageFigure
                            key={key}
                            caption={caption}
                            value={formatUnits(total[key], locale)}
                            forward={forward}
                            back={back}
                        />
                    ))}
                </ol>
                <ul aria-label="Sizes" className="flex flex-col gap-2">
                    {rows.map(({ id, label, figures }) => (
                        <li
                            key={id}
                            className="flex flex-wrap gap-x-2 gap-y-1 rounded-lg border p-3 text-sm tabular-nums"
                        >
                            <span className="font-bold">{label}</span>
                            {columns.map(({ key, caption }) => (
                                <Fragment key={key}>
                                    {' '}
                                    <span>
                                        ·{' '}
                                        <span className="text-muted-foreground">
                                            {caption}
                                        </span>{' '}
                                        {formatCount(figures[key], locale)}
                                    </span>
                                </Fragment>
                            ))}
                        </li>
                    ))}
                </ul>
            </div>
        </>
    );
}

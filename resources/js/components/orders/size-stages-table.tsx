import { Fragment, useId } from 'react';
import type { ReactNode } from 'react';
import { formatUnits, StageFigure } from '@/components/orders/stage-figure';
import { useAppSettings } from '@/hooks/use-app-settings';
import { formatCount } from '@/lib/format';

export type SizeStagesColumn = {
    /** Key of this column's figure in each row and in `total`. */
    key: string;
    /** The column's caption ("To start", "In cutting"…). */
    caption: string;
    /** Buttons shown under the Total figure of this column. */
    actions?: ReactNode;
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
 * it is given; the buttons come in as each column's `actions`.
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
            <table className="hidden w-full text-sm lg:table">
                <caption className="sr-only">
                    {productName}: units by size and stage
                </caption>
                <thead>
                    <tr className="border-b text-left text-muted-foreground">
                        <th scope="col" className="py-2 pr-3 font-medium">
                            Size
                        </th>
                        {columns.map(({ key, caption }) => (
                            <th
                                key={key}
                                scope="col"
                                className="px-3 py-2 text-right font-medium"
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
                                    className="px-3 py-2 text-right tabular-nums"
                                >
                                    {formatCount(figures[key], locale)}
                                </td>
                            ))}
                        </tr>
                    ))}
                    <tr className="align-top font-bold">
                        <th scope="row" className="py-3 pr-3 text-left">
                            Total
                        </th>
                        {columns.map(({ key, actions }) => (
                            <td key={key} className="px-3 py-3 text-right">
                                <span className="tabular-nums">
                                    {formatCount(total[key], locale)}
                                </span>
                                {actions && (
                                    <div className="mt-2 flex flex-col gap-2 font-normal">
                                        {actions}
                                    </div>
                                )}
                            </td>
                        ))}
                    </tr>
                </tbody>
            </table>

            <div className="flex flex-col gap-3 lg:hidden">
                <p id={totalId} className="text-sm font-bold">
                    Total
                </p>
                <ol
                    aria-labelledby={totalId}
                    className="grid grid-cols-2 gap-3"
                >
                    {columns.map(({ key, caption, actions }) => (
                        <StageFigure
                            key={key}
                            caption={caption}
                            value={formatUnits(total[key], locale)}
                        >
                            {actions}
                        </StageFigure>
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

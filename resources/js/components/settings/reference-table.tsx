import type { ReactNode } from 'react';
import { ReferenceRowActions } from '@/components/settings/reference-row-actions';
import type { ReferenceRowHandlers } from '@/components/settings/reference-row-actions';
import { ReferenceStatusBadge } from '@/components/settings/reference-status-badge';
import {
    Table,
    TableBody,
    TableCaption,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { cn } from '@/lib/utils';
import type { ReferenceRow } from '@/types/domain';

/** A column between Code and "Used by" (the unit table adds Dimension and Factor). */
export type ReferenceColumn<Row extends ReferenceRow> = {
    header: string;
    cell: (row: Row) => ReactNode;
};

type ReferenceTableProps<Row extends ReferenceRow> =
    ReferenceRowHandlers<Row> & {
        /** Names the table and the card list ("Units"). */
        label: string;
        /** Active rows first, then archived ones. */
        rows: Row[];
        columns?: ReferenceColumn<Row>[];
        /** "3 materials" or "Not used". */
        usedBy: (row: Row) => string;
        deleteBlock: (row: Row) => string | null;
        /** The row whose request is in flight. */
        busyId: number | null;
    };

/**
 * The rows of one catalogue list. From `xl` up it is a table; below that,
 * stacked cards with the same actions (the page's sidebar and the settings
 * menu leave a table too little room before then).
 */
export function ReferenceTable<Row extends ReferenceRow>({
    label,
    rows,
    columns = [],
    usedBy,
    deleteBlock,
    busyId,
    onEdit,
    onArchive,
    onRestore,
    onDelete,
}: ReferenceTableProps<Row>) {
    const handlers = { onEdit, onArchive, onRestore, onDelete };

    return (
        <>
            <div className="hidden rounded-lg border bg-card xl:block">
                <Table>
                    <TableCaption className="sr-only">{label}</TableCaption>
                    <TableHeader>
                        <TableRow>
                            <TableHead scope="col">Name</TableHead>
                            <TableHead scope="col">Code</TableHead>
                            {columns.map((column) => (
                                <TableHead key={column.header} scope="col">
                                    {column.header}
                                </TableHead>
                            ))}
                            <TableHead scope="col">Used by</TableHead>
                            <TableHead scope="col">Status</TableHead>
                            <TableHead scope="col" className="text-right">
                                <span className="sr-only">Actions</span>
                            </TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        {rows.map((row) => (
                            <TableRow
                                key={row.id}
                                data-archived={row.archived || undefined}
                                className={cn(
                                    row.archived && 'text-muted-foreground',
                                )}
                            >
                                <TableCell className="min-w-32 font-medium whitespace-normal">
                                    {row.name}
                                </TableCell>
                                <TableCell className="font-mono text-xs">
                                    {row.code}
                                </TableCell>
                                {columns.map((column) => (
                                    <TableCell
                                        key={column.header}
                                        className="whitespace-normal"
                                    >
                                        {column.cell(row)}
                                    </TableCell>
                                ))}
                                <TableCell className="whitespace-nowrap tabular-nums">
                                    {usedBy(row)}
                                </TableCell>
                                <TableCell>
                                    <ReferenceStatusBadge
                                        archived={row.archived}
                                    />
                                </TableCell>
                                <TableCell className="max-w-48 whitespace-normal">
                                    <ReferenceRowActions
                                        row={row}
                                        deleteBlock={deleteBlock(row)}
                                        busy={busyId === row.id}
                                        layout="table"
                                        {...handlers}
                                    />
                                </TableCell>
                            </TableRow>
                        ))}
                    </TableBody>
                </Table>
            </div>

            <ul aria-label={label} className="space-y-3 xl:hidden">
                {rows.map((row) => (
                    <li
                        key={row.id}
                        data-archived={row.archived || undefined}
                        className={cn(
                            'space-y-3 rounded-lg border bg-card p-4 text-card-foreground',
                            row.archived && 'text-muted-foreground',
                        )}
                    >
                        <div className="flex items-start justify-between gap-3">
                            <div className="min-w-0">
                                <p className="font-medium break-words">
                                    {row.name}
                                </p>
                                <p className="font-mono text-xs text-muted-foreground">
                                    {row.code}
                                </p>
                            </div>
                            <ReferenceStatusBadge archived={row.archived} />
                        </div>
                        <dl className="grid grid-cols-2 gap-2 text-sm">
                            {columns.map((column) => (
                                <div key={column.header}>
                                    <dt className="text-xs text-muted-foreground">
                                        {column.header}
                                    </dt>
                                    <dd>{column.cell(row)}</dd>
                                </div>
                            ))}
                            <div>
                                <dt className="text-xs text-muted-foreground">
                                    Used by
                                </dt>
                                <dd className="tabular-nums">{usedBy(row)}</dd>
                            </div>
                        </dl>
                        <ReferenceRowActions
                            row={row}
                            deleteBlock={deleteBlock(row)}
                            busy={busyId === row.id}
                            layout="card"
                            {...handlers}
                        />
                    </li>
                ))}
            </ul>
        </>
    );
}

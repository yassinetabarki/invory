import { Archive, ArchiveRestore, Pencil, Trash2 } from 'lucide-react';
import { useId } from 'react';
import type { MouseEvent } from 'react';
import { Button } from '@/components/ui/button';
import { cn } from '@/lib/utils';
import type { ReferenceRow } from '@/types/domain';

export type ReferenceRowHandlers<Row extends ReferenceRow> = {
    onEdit: (row: Row, trigger: HTMLElement) => void;
    onArchive: (row: Row) => void;
    onRestore: (row: Row) => void;
    onDelete: (row: Row, trigger: HTMLElement) => void;
};

type ReferenceRowActionsProps<Row extends ReferenceRow> =
    ReferenceRowHandlers<Row> & {
        row: Row;
        /** Why the row can't be deleted, or null when it can. */
        deleteBlock: string | null;
        /** A request for this row is in flight. */
        busy: boolean;
        /** `table`: icon buttons. `card`: labelled buttons. */
        layout: 'table' | 'card';
    };

/**
 * An active row can be edited or archived; an archived row can be restored or
 * deleted. Delete stays visible but disabled when something uses the row (or it
 * is a system unit), with the reason written next to it and linked with
 * `aria-describedby`. Every control names the row it acts on.
 */
export function ReferenceRowActions<Row extends ReferenceRow>({
    row,
    deleteBlock,
    busy,
    layout,
    onEdit,
    onArchive,
    onRestore,
    onDelete,
}: ReferenceRowActionsProps<Row>) {
    const reasonId = useId();
    const isTable = layout === 'table';
    const size = isTable ? 'icon' : 'sm';
    const variant = isTable ? 'ghost' : 'outline';

    function withTrigger(handler: (row: Row, trigger: HTMLElement) => void) {
        return (event: MouseEvent<HTMLButtonElement>) =>
            handler(row, event.currentTarget);
    }

    return (
        <div
            className={cn(
                'flex items-center gap-1',
                isTable ? 'flex-wrap justify-end' : 'flex-wrap gap-2',
            )}
        >
            {row.archived ? (
                <>
                    <Button
                        type="button"
                        size={size}
                        variant={variant}
                        disabled={busy}
                        aria-label={`Restore ${row.name}`}
                        title="Restore"
                        onClick={() => onRestore(row)}
                    >
                        <ArchiveRestore aria-hidden="true" />
                        {!isTable && 'Restore'}
                    </Button>
                    <Button
                        type="button"
                        size={size}
                        variant={variant}
                        disabled={busy || deleteBlock !== null}
                        aria-label={`Delete ${row.name}`}
                        aria-describedby={deleteBlock ? reasonId : undefined}
                        title="Delete"
                        className="text-destructive-foreground hover:text-destructive-foreground"
                        onClick={withTrigger(onDelete)}
                    >
                        <Trash2 aria-hidden="true" />
                        {!isTable && 'Delete'}
                    </Button>
                    {deleteBlock && (
                        <p
                            id={reasonId}
                            className={cn(
                                'basis-full text-xs text-muted-foreground',
                                isTable && 'text-right',
                            )}
                        >
                            {deleteBlock}
                        </p>
                    )}
                </>
            ) : (
                <>
                    <Button
                        type="button"
                        size={size}
                        variant={variant}
                        disabled={busy}
                        aria-label={`Edit ${row.name}`}
                        title="Edit"
                        onClick={withTrigger(onEdit)}
                    >
                        <Pencil aria-hidden="true" />
                        {!isTable && 'Edit'}
                    </Button>
                    <Button
                        type="button"
                        size={size}
                        variant={variant}
                        disabled={busy}
                        aria-label={`Archive ${row.name}`}
                        title="Archive"
                        onClick={() => onArchive(row)}
                    >
                        <Archive aria-hidden="true" />
                        {!isTable && 'Archive'}
                    </Button>
                </>
            )}
        </div>
    );
}

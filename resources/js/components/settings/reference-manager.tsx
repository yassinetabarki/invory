import { router } from '@inertiajs/react';
import { Plus } from 'lucide-react';
import type { LucideIcon } from 'lucide-react';
import { useRef, useState } from 'react';
import type { ReactNode, RefObject } from 'react';
import { toast } from 'sonner';
import Heading from '@/components/heading';
import { ReferenceTable } from '@/components/settings/reference-table';
import type { ReferenceColumn } from '@/components/settings/reference-table';
import { ConfirmDeleteDialog } from '@/components/shared/confirm-delete-dialog';
import { EmptyState } from '@/components/shared/empty-state';
import { Button } from '@/components/ui/button';
import { useAppSettings } from '@/hooks/use-app-settings';
import { formatCount } from '@/lib/format';
import { activeFirst } from '@/lib/reference';
import type { ReferenceRow } from '@/types/domain';

/** What a form dialog gets from the manager. */
export type ReferenceDialogState<Row extends ReferenceRow> = {
    open: boolean;
    /** The row being edited; null when adding. */
    row: Row | null;
    onOpenChange: (open: boolean) => void;
    /** The control that opened the dialog: it gets focus back when the dialog closes. */
    returnFocusTo: RefObject<HTMLElement | null>;
};

type ReferenceManagerProps<Row extends ReferenceRow> = {
    /** "Units": the visible heading. */
    title: string;
    description: string;
    /** Lower-case name of one row for sentences ("unit", "material category"). */
    singular: string;
    addLabel: string;
    emptyDescription: string;
    emptyIcon: LucideIcon;
    /** In server order (position, then name); archived rows are moved last here. */
    rows: Row[];
    columns?: ReferenceColumn<Row>[];
    /** What `usage_count` counts: "material" / "materials". */
    usedBy: { singular: string; plural: string };
    urls: {
        archive: (id: number) => string;
        restore: (id: number) => string;
        destroy: (id: number) => string;
    };
    /** A rule that blocks deleting a row regardless of its usage (system units). */
    blockedReason?: (row: Row) => string | null;
    /** The create / edit dialog, rendered once for the whole page. */
    renderDialog: (dialog: ReferenceDialogState<Row>) => ReactNode;
};

/** `row` stays set while the dialog closes, so its title doesn't flip to "Add" during the exit animation. */
type DialogTarget<Row extends ReferenceRow> = {
    open: boolean;
    row: Row | null;
};

/**
 * One catalogue page (units, material categories, product categories): the
 * header with "Add …", the table, and archive / restore / delete. Add and edit
 * open the page's own dialog (`renderDialog`). Every write flushes the caches of
 * the pages that show these labels.
 */
export function ReferenceManager<Row extends ReferenceRow>({
    title,
    description,
    singular,
    addLabel,
    emptyDescription,
    emptyIcon,
    rows,
    columns,
    usedBy,
    urls,
    blockedReason,
    renderDialog,
}: ReferenceManagerProps<Row>) {
    const { locale } = useAppSettings();
    const [dialog, setDialog] = useState<DialogTarget<Row>>({
        open: false,
        row: null,
    });
    const [deleting, setDeleting] = useState<Row | null>(null);
    const [deleteProcessing, setDeleteProcessing] = useState(false);
    const [busyId, setBusyId] = useState<number | null>(null);
    const triggerRef = useRef<HTMLElement | null>(null);

    const ordered = activeFirst(rows);

    function usedByText(row: Row): string {
        if (row.usage_count === 0) {
            return 'Not used';
        }

        return `${formatCount(row.usage_count, locale)} ${
            row.usage_count === 1 ? usedBy.singular : usedBy.plural
        }`;
    }

    function deleteBlock(row: Row): string | null {
        return (
            blockedReason?.(row) ??
            (row.usage_count > 0 ? `Used by ${usedByText(row)}` : null)
        );
    }

    function open(row: Row | null, trigger: HTMLElement) {
        triggerRef.current = trigger;
        setDialog({ open: true, row });
    }

    function request(
        row: Row,
        send: (callbacks: {
            onStart: () => void;
            onFinish: () => void;
        }) => void,
    ) {
        send({
            onStart: () => setBusyId(row.id),
            onFinish: () => setBusyId(null),
        });
    }

    function archive(row: Row) {
        request(row, (callbacks) =>
            router.patch(
                urls.archive(row.id),
                {},
                {
                    preserveScroll: true,
                    ...callbacks,
                },
            ),
        );
    }

    // Restoring is refused while an active row has the same name: the server
    // answers with a validation error on `name`, shown as an error toast.
    function restore(row: Row) {
        request(row, (callbacks) =>
            router.patch(
                urls.restore(row.id),
                {},
                {
                    preserveScroll: true,
                    onError: (errors) =>
                        toast.error(
                            errors.name ??
                                Object.values(errors)[0] ??
                                `The ${singular} could not be restored.`,
                        ),
                    ...callbacks,
                },
            ),
        );
    }

    function confirmDelete() {
        if (deleting === null) {
            return;
        }

        router.delete(urls.destroy(deleting.id), {
            preserveScroll: true,
            onStart: () => setDeleteProcessing(true),
            onFinish: () => {
                setDeleteProcessing(false);
                setDeleting(null);
            },
        });
    }

    const addButton = (
        <Button
            type="button"
            onClick={(event) => open(null, event.currentTarget)}
        >
            <Plus aria-hidden="true" />
            {addLabel}
        </Button>
    );

    return (
        <>
            <h1 className="sr-only">{title} settings</h1>

            <div className="space-y-6">
                <div className="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                    <Heading
                        variant="small"
                        title={title}
                        description={description}
                    />
                    <div className="shrink-0">{addButton}</div>
                </div>

                {ordered.length === 0 ? (
                    <EmptyState
                        icon={emptyIcon}
                        title={`No ${title.toLowerCase()} yet`}
                        description={emptyDescription}
                        action={addButton}
                    />
                ) : (
                    <ReferenceTable
                        label={title}
                        rows={ordered}
                        columns={columns}
                        usedBy={usedByText}
                        deleteBlock={deleteBlock}
                        busyId={busyId}
                        onEdit={open}
                        onArchive={archive}
                        onRestore={restore}
                        onDelete={(row, trigger) => {
                            triggerRef.current = trigger;
                            setDeleting(row);
                        }}
                    />
                )}
            </div>

            {renderDialog({
                open: dialog.open,
                row: dialog.row,
                onOpenChange: (next) => {
                    if (!next) {
                        setDialog((current) => ({ ...current, open: false }));
                    }
                },
                returnFocusTo: triggerRef,
            })}

            <ConfirmDeleteDialog
                open={deleting !== null}
                onOpenChange={(next) => {
                    if (!next && !deleteProcessing) {
                        setDeleting(null);
                    }
                }}
                title={`Delete ${singular}?`}
                description={
                    <p>
                        <strong>{deleting?.name}</strong> ({deleting?.code})
                        will be removed for good. This cannot be undone.
                    </p>
                }
                confirmLabel={`Delete ${singular}`}
                processing={deleteProcessing}
                returnFocusTo={triggerRef}
                onConfirm={confirmDelete}
            />
        </>
    );
}

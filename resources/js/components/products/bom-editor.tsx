import { Info, Plus } from 'lucide-react';
import { useEffect, useId, useRef, useState } from 'react';
import { BOM_GRID, BomEditorRow } from '@/components/products/bom-editor-row';
import { errorsForRow, newBomRow } from '@/components/products/bom-rows';
import type { BomRow } from '@/components/products/bom-rows';
import { Button } from '@/components/ui/button';
import { cn } from '@/lib/utils';
import type { MaterialOption, ProductComponentFormRow } from '@/types/domain';

type BomEditorProps = {
    rows: BomRow[];
    /**
     * The materials the rows already use (name, unit, cost and stock), so
     * they show without a request. Only seeds the editor; the picker adds
     * every option it loads.
     */
    knownMaterials?: readonly MaterialOption[];
    /**
     * The form's errors by key: `components` for the list as a whole and
     * `components.N.field` for a row's field (`N` is the row's position, from 0).
     */
    errors: Record<string, string | undefined>;
    onChange: (rows: BomRow[]) => void;
};

/**
 * The "Composition" card of the product form: the materials that make one
 * unit. Rows can be added, removed and reordered (Move up / Move down, which
 * work with the keyboard). A material can only be used on one row.
 */
export function BomEditor({
    rows,
    knownMaterials = [],
    errors,
    onChange,
}: BomEditorProps) {
    const idPrefix = useId();
    // id → material for every material seen: seeded by the caller, then fed by the picker.
    const [catalog, setCatalog] = useState<ReadonlyMap<number, MaterialOption>>(
        () =>
            new Map(knownMaterials.map((material) => [material.id, material])),
    );
    const containerRef = useRef<HTMLElement>(null);
    // The picker that is open (one at a time), by row uid.
    const [openUid, setOpenUid] = useState<string | null>(null);
    // Where focus goes after the next render (a reorder or a removal moves
    // the focused element).
    const pendingFocus = useRef<string | null>(null);

    useEffect(() => {
        const selector = pendingFocus.current;

        if (selector === null) {
            return;
        }

        pendingFocus.current = null;

        const target =
            containerRef.current?.querySelector<HTMLElement>(selector);

        if (target && !target.hasAttribute('disabled')) {
            target.focus();
        }
    });

    function updateRow(uid: string, patch: Partial<ProductComponentFormRow>) {
        onChange(
            rows.map((row) => (row.uid === uid ? { ...row, ...patch } : row)),
        );
    }

    function rememberMaterials(options: MaterialOption[]) {
        setCatalog((current) => {
            const next = new Map(current);

            for (const option of options) {
                next.set(option.id, option);
            }

            return next;
        });
    }

    function addRow() {
        const row = newBomRow();

        onChange([...rows, row]);
        // A new row opens its picker, so the next keystrokes search materials.
        setOpenUid(row.uid);
    }

    function removeRow(index: number) {
        const neighbour = rows[index + 1] ?? rows[index - 1];

        pendingFocus.current = neighbour
            ? `[data-row-uid="${neighbour.uid}"] [data-action="material"]`
            : '[data-action="add-material"]';
        onChange(rows.filter((_, position) => position !== index));
    }

    function moveRow(index: number, direction: 'up' | 'down') {
        const target = direction === 'up' ? index - 1 : index + 1;

        if (target < 0 || target >= rows.length) {
            return;
        }

        const moved = rows[index];
        const next = [...rows];

        next[index] = next[target];
        next[target] = moved;

        // Keep focus on the button that was used; at the end of the list the
        // button is disabled, so focus follows to the opposite one.
        pendingFocus.current = `[data-row-uid="${moved.uid}"][data-action="${direction}"]`;

        if (
            (direction === 'up' && target === 0) ||
            (direction === 'down' && target === rows.length - 1)
        ) {
            pendingFocus.current = `[data-row-uid="${moved.uid}"][data-action="${direction === 'up' ? 'down' : 'up'}"]`;
        }

        onChange(next);
    }

    // Picked on any row: the picker asks the server to send these too.
    const pickedIds = rows.flatMap((row) =>
        row.material_id === '' ? [] : [row.material_id],
    );
    const countLabel = `${rows.length} ${rows.length === 1 ? 'material' : 'materials'}`;

    return (
        <section
            ref={containerRef}
            aria-labelledby={`${idPrefix}-title`}
            className="@container rounded-xl border bg-card p-5 text-card-foreground"
        >
            <div className="flex flex-wrap items-end justify-between gap-3">
                <div className="space-y-0.5">
                    <h2 id={`${idPrefix}-title`} className="text-lg font-bold">
                        Composition
                    </h2>
                    <p className="text-sm text-muted-foreground">
                        Materials for <strong>one</strong> unit, in each
                        material&apos;s own unit. Waste is added on top of the
                        quantity.
                    </p>
                </div>
                <span className="text-sm text-muted-foreground">
                    {countLabel}
                </span>
            </div>

            {errors.components && (
                <p
                    role="alert"
                    className="mt-3 text-sm text-destructive-foreground"
                >
                    {errors.components}
                </p>
            )}

            {rows.length === 0 ? (
                <p className="mt-4 rounded-lg border border-dashed px-4 py-6 text-center text-sm text-muted-foreground">
                    No materials yet. Add the materials that make one unit.
                </p>
            ) : (
                <div className="mt-4 flex flex-col gap-3">
                    <div
                        aria-hidden="true"
                        className={cn(
                            'hidden gap-3 text-xs font-semibold text-muted-foreground @4xl:grid',
                            BOM_GRID,
                        )}
                    >
                        <span>Material</span>
                        <span>Qty per unit</span>
                        <span>Waste</span>
                        <span>Placement notes</span>
                        <span />
                    </div>
                    <ol
                        aria-label="Materials in this composition"
                        className="flex flex-col gap-3"
                    >
                        {rows.map((row, index) => (
                            <li key={row.uid}>
                                <BomEditorRow
                                    row={row}
                                    number={index + 1}
                                    isFirst={index === 0}
                                    isLast={index === rows.length - 1}
                                    idPrefix={idPrefix}
                                    material={
                                        row.material_id === ''
                                            ? undefined
                                            : catalog.get(row.material_id)
                                    }
                                    include={pickedIds}
                                    usedIds={
                                        new Set(
                                            rows.flatMap((other) =>
                                                other.uid !== row.uid &&
                                                other.material_id !== ''
                                                    ? [other.material_id]
                                                    : [],
                                            ),
                                        )
                                    }
                                    errors={errorsForRow(errors, index)}
                                    pickerOpen={openUid === row.uid}
                                    onPickerOpenChange={(open) =>
                                        setOpenUid((current) =>
                                            open
                                                ? row.uid
                                                : current === row.uid
                                                  ? null
                                                  : current,
                                        )
                                    }
                                    onChange={(patch) =>
                                        updateRow(row.uid, patch)
                                    }
                                    onMaterialsLoaded={rememberMaterials}
                                    onMove={(direction) =>
                                        moveRow(index, direction)
                                    }
                                    onRemove={() => removeRow(index)}
                                />
                            </li>
                        ))}
                    </ol>
                </div>
            )}

            <div className="mt-4 flex flex-wrap items-center justify-between gap-3">
                <Button
                    type="button"
                    variant="outline"
                    data-action="add-material"
                    className="border-dashed text-primary"
                    onClick={addRow}
                >
                    <Plus aria-hidden="true" />
                    Add material
                </Button>
                <p className="flex items-center gap-1.5 text-xs text-muted-foreground">
                    <Info className="size-3.5 shrink-0" aria-hidden="true" />
                    Unit cost and capacity are recalculated from current stock
                    when you save.
                </p>
            </div>
        </section>
    );
}

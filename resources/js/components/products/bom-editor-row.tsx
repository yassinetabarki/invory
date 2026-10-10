import { ArrowDown, ArrowUp, Trash2 } from 'lucide-react';
import { DecimalInput } from '@/components/products/decimal-input';
import type { BomRow, RowErrors } from '@/components/products/bom-rows';
import { MaterialCombobox } from '@/components/products/material-combobox';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { EMPTY_VALUE } from '@/lib/format';
import { cn } from '@/lib/utils';
import type { MaterialOption, ProductComponentFormRow } from '@/types/domain';

/** The responsive column layout shared by the rows and their header. */
export const BOM_GRID =
    '@4xl:grid-cols-[minmax(14rem,2fr)_9rem_7rem_minmax(10rem,1.4fr)_7.5rem]';

type BomEditorRowProps = {
    row: BomRow;
    /** 1-based position, for the group's name. */
    number: number;
    isFirst: boolean;
    isLast: boolean;
    /** Prefix that makes this editor's element ids unique on the page. */
    idPrefix: string;
    /** The row's material, when known (picked rows are remembered by the editor). */
    material: MaterialOption | undefined;
    /** Ids picked on the other rows. */
    usedIds: ReadonlySet<number>;
    /** Every material id picked on the form. */
    include: readonly number[];
    errors: RowErrors;
    pickerOpen: boolean;
    onPickerOpenChange: (open: boolean) => void;
    onChange: (patch: Partial<ProductComponentFormRow>) => void;
    /** Every option the picker loads, for the editor to remember. */
    onMaterialsLoaded: (options: MaterialOption[]) => void;
    onMove: (direction: 'up' | 'down') => void;
    onRemove: () => void;
};

/** An error message tied to its field by id. */
function FieldError({ id, message }: { id: string; message?: string }) {
    if (!message) {
        return null;
    }

    return (
        <p id={id} className="text-sm text-destructive-foreground">
            {message}
        </p>
    );
}

/**
 * One material of the composition: picker, quantity (with the material's unit
 * as suffix), waste %, placement notes, and move / remove buttons. Below the
 * `@4xl` container width it is a card with labelled fields; above it, one
 * line under shared column headers (the labels become screen-reader-only).
 */
export function BomEditorRow({
    row,
    number,
    isFirst,
    isLast,
    idPrefix,
    material,
    usedIds,
    include,
    errors,
    pickerOpen,
    onPickerOpenChange,
    onChange,
    onMaterialsLoaded,
    onMove,
    onRemove,
}: BomEditorRowProps) {
    const label =
        row.material_id === ''
            ? 'new material'
            : (material?.name ?? `material ${row.material_id}`);

    const base = `${idPrefix}-${row.uid}`;
    const ids = {
        material: `${base}-material`,
        materialLabel: `${base}-material-label`,
        quantity: `${base}-quantity`,
        waste: `${base}-waste`,
        notes: `${base}-notes`,
    };
    const errorIds = {
        material: `${ids.material}-error`,
        quantity: `${ids.quantity}-error`,
        waste: `${ids.waste}-error`,
        notes: `${ids.notes}-error`,
    };

    return (
        <div
            role="group"
            aria-label={`Row ${number}: ${label}`}
            data-row-uid={row.uid}
            className={cn(
                'grid grid-cols-2 gap-3 rounded-lg border p-3 @4xl:items-start @4xl:border-0 @4xl:p-0',
                BOM_GRID,
            )}
        >
            <div className="col-span-2 flex min-w-0 flex-col gap-1.5 @4xl:col-span-1">
                <Label
                    id={ids.materialLabel}
                    htmlFor={ids.material}
                    className="@4xl:sr-only"
                >
                    Material
                </Label>
                <MaterialCombobox
                    id={ids.material}
                    labelId={ids.materialLabel}
                    aria-invalid={errors.material_id ? true : undefined}
                    aria-describedby={
                        errors.material_id ? errorIds.material : undefined
                    }
                    selected={material}
                    value={row.material_id}
                    usedIds={usedIds}
                    include={include}
                    open={pickerOpen}
                    onOpenChange={onPickerOpenChange}
                    onLoaded={onMaterialsLoaded}
                    onSelect={(picked) => {
                        onChange({ material_id: picked.id });
                        onPickerOpenChange(false);
                    }}
                />
                <FieldError
                    id={errorIds.material}
                    message={errors.material_id}
                />
            </div>

            <div className="flex min-w-0 flex-col gap-1.5">
                <Label htmlFor={ids.quantity} className="@4xl:sr-only">
                    Quantity per unit
                </Label>
                <DecimalInput
                    id={ids.quantity}
                    aria-label={`Quantity per unit for ${label}`}
                    aria-invalid={errors.quantity ? true : undefined}
                    aria-describedby={
                        errors.quantity ? errorIds.quantity : undefined
                    }
                    value={row.quantity}
                    placeholder="0"
                    addonEnd={material?.unit ?? EMPTY_VALUE}
                    onChange={(event) =>
                        onChange({ quantity: event.target.value })
                    }
                />
                <FieldError id={errorIds.quantity} message={errors.quantity} />
            </div>

            <div className="flex min-w-0 flex-col gap-1.5">
                <Label htmlFor={ids.waste} className="@4xl:sr-only">
                    Waste
                </Label>
                <DecimalInput
                    id={ids.waste}
                    aria-label={`Waste percent for ${label}`}
                    aria-invalid={errors.waste_percent ? true : undefined}
                    aria-describedby={
                        errors.waste_percent ? errorIds.waste : undefined
                    }
                    value={row.waste_percent}
                    addonEnd="%"
                    onChange={(event) =>
                        onChange({ waste_percent: event.target.value })
                    }
                />
                <FieldError
                    id={errorIds.waste}
                    message={errors.waste_percent}
                />
            </div>

            <div className="col-span-2 flex min-w-0 flex-col gap-1.5 @4xl:col-span-1">
                <Label htmlFor={ids.notes} className="@4xl:sr-only">
                    Placement notes
                </Label>
                <Input
                    id={ids.notes}
                    aria-label={`Placement notes for ${label}`}
                    aria-invalid={errors.notes ? true : undefined}
                    aria-describedby={errors.notes ? errorIds.notes : undefined}
                    value={row.notes}
                    placeholder="e.g. Front placket"
                    autoComplete="off"
                    onChange={(event) =>
                        onChange({ notes: event.target.value })
                    }
                />
                <FieldError id={errorIds.notes} message={errors.notes} />
            </div>

            <div className="col-span-2 flex items-center justify-end gap-1 @4xl:col-span-1">
                <Button
                    type="button"
                    variant="ghost"
                    size="icon"
                    aria-label={`Move ${label} up`}
                    title="Move up"
                    disabled={isFirst}
                    data-row-uid={row.uid}
                    data-action="up"
                    onClick={() => onMove('up')}
                >
                    <ArrowUp aria-hidden="true" />
                </Button>
                <Button
                    type="button"
                    variant="ghost"
                    size="icon"
                    aria-label={`Move ${label} down`}
                    title="Move down"
                    disabled={isLast}
                    data-row-uid={row.uid}
                    data-action="down"
                    onClick={() => onMove('down')}
                >
                    <ArrowDown aria-hidden="true" />
                </Button>
                <Button
                    type="button"
                    variant="ghost"
                    size="icon"
                    aria-label={`Remove ${label}`}
                    title="Remove"
                    className="text-destructive-foreground hover:text-destructive-foreground"
                    onClick={onRemove}
                >
                    <Trash2 aria-hidden="true" />
                </Button>
            </div>
        </div>
    );
}

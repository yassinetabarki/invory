import type {
    MaterialOption,
    ProductComponentFormRow,
    ProductComponentLine,
} from '@/types/domain';

/**
 * A composition row in the form: the submitted fields plus a client-only
 * `uid`, so React can keep a row's state when rows are reordered or removed.
 * The `uid` is never sent to the server.
 */
export type BomRow = ProductComponentFormRow & { uid: string };

/** A row's validation errors, keyed by the field name (`quantity`…). */
export type RowErrors = Partial<Record<keyof ProductComponentFormRow, string>>;

let rowCounter = 0;

/** A fresh client-side row key: unique on the page, and never the row's index. */
export function createRowUid(): string {
    rowCounter += 1;

    return `row-${rowCounter}`;
}

/** An empty composition row; waste starts at 0. */
export function newBomRow(): BomRow {
    return {
        uid: createRowUid(),
        material_id: '',
        quantity: '',
        waste_percent: '0',
        notes: '',
    };
}

/** The row fields the server can report errors for. */
const ROW_FIELDS = [
    'material_id',
    'quantity',
    'waste_percent',
    'notes',
] as const satisfies readonly (keyof ProductComponentFormRow)[];

/**
 * The errors of row `index` out of the form's flat `components.N.field`
 * keys (`N` counts from 0, in the order the rows were submitted).
 */
export function errorsForRow(
    errors: Record<string, string | undefined>,
    index: number,
): RowErrors {
    const rowErrors: RowErrors = {};

    for (const field of ROW_FIELDS) {
        const message = errors[`components.${index}.${field}`];

        if (message) {
            rowErrors[field] = message;
        }
    }

    return rowErrors;
}

/** The submitted shape of a row: its form fields, without the client-only `uid`. */
export function toSubmittedRow(row: BomRow): ProductComponentFormRow {
    return {
        material_id: row.material_id,
        quantity: row.quantity,
        waste_percent: row.waste_percent,
        notes: row.notes,
    };
}

/**
 * The picker's view of the material on a saved composition line, so the edit
 * page shows its rows (name, unit, cost, stock) without asking the server.
 * `unitLabel` is the unit's display name, which a line does not carry.
 */
export function materialOptionFromLine(
    line: ProductComponentLine,
    unitLabel: string,
): MaterialOption {
    return {
        id: line.material.id,
        sku: line.material.sku,
        name: line.material.name,
        unit: line.material.unit,
        unit_label: unitLabel,
        unit_cost: line.unit_cost,
        quantity_on_hand: line.stock_on_hand,
        stock_status: line.material.stock_status,
    };
}

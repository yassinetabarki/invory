import { isFilledQuantity } from '@/components/products/decimals';
import type {
    MaterialOption,
    ProductComponentFormRow,
    ProductComponentLine,
    QuantityCell,
    Size,
} from '@/types/domain';

/** The cells of a "By colour and size" line: colour id → size id → the text typed ('' or missing = no cell). */
export type QuantityGrid = ProductComponentFormRow['quantities'];

/** The fields of a composition row the form holds and submits (INV-16: its mode and its cells). */
export type BomRowFields = ProductComponentFormRow;

/**
 * A composition row in the form: the submitted fields plus a client-only
 * `uid`, so React can keep a row's state when rows are reordered or removed.
 * The `uid` is never sent to the server.
 */
export type BomRow = BomRowFields & { uid: string };

/** A row's validation errors, keyed by the field name (`quantity`, `quantities`…). */
export type RowErrors = Partial<Record<keyof BomRowFields, string>>;

/** A server error on one cell of a row's grid. */
export type CellError = { colourId: number; sizeId: number; message: string };

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
        quantity_mode: 'same',
        quantities: {},
    };
}

/** The row fields the server can report errors for. */
const ROW_FIELDS = [
    'material_id',
    'quantity',
    'quantities',
    'waste_percent',
    'notes',
] as const satisfies readonly (keyof BomRowFields)[];

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

/**
 * The errors on the cells of row `index` (`components.N.quantities.colour.size`),
 * in the order the server sent them.
 */
export function cellErrorsForRow(
    errors: Record<string, string | undefined>,
    index: number,
): CellError[] {
    const pattern = new RegExp(
        String.raw`^components\.${index}\.quantities\.(\d+)\.(\d+)$`,
    );

    return Object.entries(errors).flatMap(([key, message]) => {
        const match = pattern.exec(key);

        return match && message
            ? [
                  {
                      colourId: Number(match[1]),
                      sizeId: Number(match[2]),
                      message,
                  },
              ]
            : [];
    });
}

/** The text of one cell; '' when it has none. */
export function cellValue(
    grid: QuantityGrid,
    colourId: number,
    sizeId: number,
): string {
    return grid[colourId]?.[sizeId] ?? '';
}

/** The grid of a saved line: its cells' decimal strings, as the server sent them. */
export function gridFromCells(cells: readonly QuantityCell[]): QuantityGrid {
    const grid: QuantityGrid = {};

    for (const cell of cells) {
        grid[cell.colour_id] = {
            ...grid[cell.colour_id],
            [cell.size_id]: cell.quantity,
        };
    }

    return grid;
}

/** The filled cells among these colours and sizes (the ones on the form now). */
export function countFilledCells(
    grid: QuantityGrid,
    colours: readonly { id: number }[],
    sizes: readonly Size[],
): number {
    return colours.reduce(
        (total, colour) =>
            total +
            sizes.filter((size) =>
                isFilledQuantity(cellValue(grid, colour.id, size.id)),
            ).length,
        0,
    );
}

/**
 * The line's summary: "4 of 8 cells · Indigo only" (cells in one or two
 * colours name them), "12 of 20 cells" (more) or "No cells set yet".
 */
export function summariseCells(
    grid: QuantityGrid,
    colours: readonly { id: number; name: string }[],
    sizes: readonly Size[],
): string {
    const filled = countFilledCells(grid, colours, sizes);

    if (filled === 0) {
        return 'No cells set yet';
    }

    const count = `${filled} of ${colours.length * sizes.length} cells`;
    const named = colours.filter(
        (colour) => countFilledCells(grid, [colour], sizes) > 0,
    );

    return named.length > 2
        ? count
        : `${count} · ${named.map((colour) => colour.name).join(' and ')} only`;
}

/** Every cell of these colours and sizes holding the text typed, or none when nothing was typed. */
export function prefilledGrid(
    quantity: string,
    colours: readonly { id: number }[],
    sizes: readonly Size[],
): QuantityGrid {
    if (!isFilledQuantity(quantity)) {
        return {};
    }

    return Object.fromEntries(
        colours.map((colour) => [
            colour.id,
            Object.fromEntries(sizes.map((size) => [size.id, quantity])),
        ]),
    );
}

/** The filled cells of a grid as "colourId:sizeId" → the text typed (trimmed). */
function filledEntries(grid: QuantityGrid): Map<string, string> {
    const entries = new Map<string, string>();

    for (const [colourId, cells] of Object.entries(grid)) {
        for (const [sizeId, value] of Object.entries(cells)) {
            if (isFilledQuantity(value)) {
                entries.set(`${colourId}:${sizeId}`, value.trim());
            }
        }
    }

    return entries;
}

/** True when both grids hold the same filled cells with the same text (an empty cell and a missing one are alike). */
export function sameCells(a: QuantityGrid, b: QuantityGrid): boolean {
    const left = filledEntries(a);
    const right = filledEntries(b);

    return (
        left.size === right.size &&
        [...left].every(([key, value]) => right.get(key) === value)
    );
}

/** What a row sends: its form fields without the client-only `uid`, and for a "By colour and size" line its filled cells. */
export type SubmittedRow = Omit<ProductComponentFormRow, 'quantities'> &
    Partial<Pick<ProductComponentFormRow, 'quantities'>>;

/**
 * The submitted shape of a row. A "By colour and size" line sends only its
 * filled cells (not '' and not 0) of the colours ticked and the sizes pressed
 * on the form now; a colour taken off leaves its cells out.
 */
export function toSubmittedRow(
    row: BomRow,
    colourIds: readonly number[],
    sizeIds: readonly number[],
): SubmittedRow {
    const submitted: SubmittedRow = {
        material_id: row.material_id,
        quantity_mode: row.quantity_mode,
        quantity: row.quantity,
        waste_percent: row.waste_percent,
        notes: row.notes,
    };

    if (row.quantity_mode === 'by_variant') {
        submitted.quantities = {};

        for (const colourId of colourIds) {
            const cells = sizeIds.flatMap((sizeId) => {
                const value = cellValue(row.quantities, colourId, sizeId);

                return isFilledQuantity(value)
                    ? [[sizeId, value] as const]
                    : [];
            });

            if (cells.length > 0) {
                submitted.quantities[colourId] = Object.fromEntries(cells);
            }
        }
    }

    return submitted;
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

/**
 * The typed cells (not '' and not 0) of these sizes over every "By colour and
 * size" line. With `colourIds`, only the cells of those colours count: a colour
 * taken off the form leaves its cells out of the payload.
 */
export function countTypedCells(
    rows: readonly BomRow[],
    sizeIds: readonly number[],
    colourIds?: readonly number[],
): number {
    return rows.reduce((total, row) => {
        if (row.quantity_mode !== 'by_variant') {
            return total;
        }

        return Object.entries(row.quantities).reduce(
            (sum, [colourId, cells]) =>
                colourIds && !colourIds.includes(Number(colourId))
                    ? sum
                    : sum +
                      sizeIds.filter((sizeId) =>
                          isFilledQuantity(cells[sizeId] ?? ''),
                      ).length,
            total,
        );
    }, 0);
}

/** The rows with the cells of these sizes cleared on every "By colour and size" line. */
export function clearSizeCells(
    rows: readonly BomRow[],
    sizeIds: readonly number[],
): BomRow[] {
    return rows.map((row) =>
        row.quantity_mode !== 'by_variant'
            ? row
            : {
                  ...row,
                  quantities: Object.fromEntries(
                      Object.entries(row.quantities).map(
                          ([colourId, cells]) => [
                              colourId,
                              Object.fromEntries(
                                  Object.entries(cells).filter(
                                      ([sizeId]) =>
                                          !sizeIds.includes(Number(sizeId)),
                                  ),
                              ),
                          ],
                      ),
                  ),
              },
    );
}

/** The grid with one cell set; the grid it came from is left as it was. */
export function withCell(
    grid: QuantityGrid,
    colourId: number,
    sizeId: number,
    value: string,
): QuantityGrid {
    return { ...grid, [colourId]: { ...grid[colourId], [sizeId]: value } };
}

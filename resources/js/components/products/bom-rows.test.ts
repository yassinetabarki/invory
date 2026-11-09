import { describe, expect, it } from 'vite-plus/test';
import {
    countTypedCells,
    createRowUid,
    errorsForRow,
    newBomRow,
    sameCells,
    toSubmittedRow,
} from '@/components/products/bom-rows';
import type { BomRow } from '@/components/products/bom-rows';
import { matchesSearch } from '@/components/products/material-search';

describe('createRowUid / newBomRow', () => {
    it('gives every row its own key, never an index', () => {
        const uids = new Set([
            createRowUid(),
            createRowUid(),
            newBomRow().uid,
            newBomRow().uid,
        ]);

        expect(uids.size).toBe(4);
    });

    it('starts blank with 0 waste', () => {
        expect(newBomRow()).toMatchObject({
            material_id: '',
            quantity: '',
            waste_percent: '0',
            notes: '',
            quantity_mode: 'same',
            quantities: {},
        });
    });
});

describe('errorsForRow', () => {
    const errors = {
        'components.0.quantity': 'Row 0 quantity.',
        'components.1.material_id': 'Row 1 material.',
        'components.1.waste_percent': 'Row 1 waste.',
        'components.1.notes': 'Row 1 notes.',
        'components.10.quantity': 'Row 10 quantity.',
        components: 'The list.',
        name: 'Unrelated.',
        'components.1.unknown': 'Not a row field.',
    };

    it('picks the errors of one row by its position, counted from 0', () => {
        expect(errorsForRow(errors, 1)).toEqual({
            material_id: 'Row 1 material.',
            waste_percent: 'Row 1 waste.',
            notes: 'Row 1 notes.',
        });
        expect(errorsForRow(errors, 0)).toEqual({
            quantity: 'Row 0 quantity.',
        });
    });

    it('does not mix up row 1 with row 10', () => {
        expect(errorsForRow(errors, 10)).toEqual({
            quantity: 'Row 10 quantity.',
        });
        expect(errorsForRow(errors, 1)).not.toHaveProperty('quantity');
    });

    it('returns nothing for a row without errors, and ignores empty messages', () => {
        expect(errorsForRow(errors, 5)).toEqual({});
        expect(errorsForRow({ 'components.0.quantity': undefined }, 0)).toEqual(
            {},
        );
    });
});

describe('toSubmittedRow', () => {
    it('drops the client-only uid and keeps the text of the fields as typed', () => {
        const row: BomRow = {
            uid: 'row-1',
            material_id: 4,
            quantity: '1.80',
            waste_percent: '10',
            notes: 'Body',
            quantity_mode: 'same',
            quantities: {},
        };

        expect(toSubmittedRow(row, [10], [1, 2])).toEqual({
            material_id: 4,
            quantity_mode: 'same',
            quantity: '1.80',
            waste_percent: '10',
            notes: 'Body',
        });
        expect(toSubmittedRow(row, [10], [1, 2])).not.toHaveProperty('uid');
    });

    it('keeps a blank material as an empty string for the server to reject', () => {
        expect(toSubmittedRow(newBomRow(), [], []).material_id).toBe('');
    });
});

describe('countTypedCells', () => {
    const line = (quantities: BomRow['quantities']): BomRow => ({
        ...newBomRow(),
        quantity_mode: 'by_variant',
        quantities,
    });
    // Colour 8 holds sizes 1 (typed), 2 (0, no cell) and 4 (typed); colour 9 size 4 (typed).
    const rows: BomRow[] = [
        line({ 8: { 1: '1.6', 2: '0.0000', 4: '2.2' }, 9: { 4: '2' } }),
        line({ 8: { 4: '' }, 9: { 4: '0.5' } }),
        // A "Same for every variant" line never counts, whatever its grid holds.
        { ...newBomRow(), quantity: '1', quantities: { 8: { 4: '9' } } },
    ];

    it('counts the filled cells of the sizes asked, over every line', () => {
        expect(countTypedCells(rows, [4])).toBe(3);
        expect(countTypedCells(rows, [1, 4])).toBe(4);
        expect(countTypedCells(rows, [2])).toBe(0);
        expect(countTypedCells(rows, [])).toBe(0);
    });

    it('leaves out the colours that are not on the form', () => {
        expect(countTypedCells(rows, [4], [9])).toBe(2);
    });
});

describe('matchesSearch (the material picker)', () => {
    const option = 'Metal shank button 17mm MAT-BTN-017';

    it('matches the name and the SKU, ignoring case', () => {
        expect(matchesSearch(option, 'shank')).toBe(1);
        expect(matchesSearch(option, 'SHANK')).toBe(1);
        expect(matchesSearch(option, 'btn-017')).toBe(1);
    });

    it('needs every word, in any order', () => {
        expect(matchesSearch(option, 'button metal')).toBe(1);
        expect(matchesSearch(option, 'button zipper')).toBe(0);
    });

    it('shows everything for an empty or blank search', () => {
        expect(matchesSearch(option, '')).toBe(1);
        expect(matchesSearch(option, '   ')).toBe(1);
    });

    it('does not match scattered letters', () => {
        expect(matchesSearch('Metal zipper 18cm MAT-ZIP-018', 'zzzz')).toBe(0);
    });
});

describe('sameCells', () => {
    it('compares the filled cells as text; an empty cell and a missing one are alike', () => {
        expect(
            sameCells({ 1: { 2: '1.8' } }, { 1: { 2: ' 1.8 ', 3: '' } }),
        ).toBe(true);
        expect(sameCells({}, { 1: { 2: '0' } })).toBe(true);
        expect(sameCells({ 1: { 2: '1.8' } }, { 1: { 2: '1.80' } })).toBe(
            false,
        );
        expect(sameCells({ 1: { 2: '1.8' } }, {})).toBe(false);
    });
});

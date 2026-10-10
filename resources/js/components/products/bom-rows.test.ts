import { describe, expect, it } from 'vite-plus/test';
import {
    createRowUid,
    errorsForRow,
    newBomRow,
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
        };

        expect(toSubmittedRow(row)).toEqual({
            material_id: 4,
            quantity: '1.80',
            waste_percent: '10',
            notes: 'Body',
        });
        expect(toSubmittedRow(row)).not.toHaveProperty('uid');
    });

    it('keeps a blank material as an empty string for the server to reject', () => {
        expect(toSubmittedRow(newBomRow()).material_id).toBe('');
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

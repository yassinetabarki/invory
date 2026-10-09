import { describe, expect, it } from 'vite-plus/test';
import {
    activeFirst,
    categoryOptions,
    describeFactor,
    unitLabel,
    unitOptions,
} from '@/lib/reference';
import {
    archived,
    referenceMaterialCategories,
    referenceUnits,
} from '@/test/fixtures/reference';
import type { UnitOption } from '@/types/domain';

function unit(code: string): UnitOption {
    const found = referenceUnits.find((option) => option.code === code);

    if (!found) {
        throw new Error(`No fixture unit ${code}`);
    }

    return found;
}

describe('categoryOptions', () => {
    it('lists active categories as code and name, in server order', () => {
        const options = categoryOptions(referenceMaterialCategories);

        expect(options).toHaveLength(12);
        expect(options.slice(0, 3)).toEqual([
            { value: 'fabric', label: 'Fabric' },
            { value: 'lining', label: 'Lining' },
            { value: 'interlining', label: 'Interlining' },
        ]);
    });

    it('hides archived categories', () => {
        const list = [
            ...referenceMaterialCategories,
            archived({
                id: 99,
                code: 'elastic',
                name: 'Elastic',
                position: 13,
                archived: false,
            }),
        ];

        expect(
            categoryOptions(list).map((option) => option.value),
        ).not.toContain('elastic');
    });

    it('keeps the archived category the record uses, where the server put it', () => {
        const list = [
            referenceMaterialCategories[0],
            archived(referenceMaterialCategories[1]),
            referenceMaterialCategories[2],
        ];

        expect(categoryOptions(list, 'lining')).toEqual([
            { value: 'fabric', label: 'Fabric' },
            { value: 'lining', label: 'Lining' },
            { value: 'interlining', label: 'Interlining' },
        ]);
    });

    it('keeps only the archived row that was asked for', () => {
        const list = [
            archived(referenceMaterialCategories[0]),
            archived(referenceMaterialCategories[1]),
        ];

        expect(categoryOptions(list, 'lining').map((o) => o.value)).toEqual([
            'lining',
        ]);
        expect(categoryOptions(list, null)).toEqual([]);
        expect(categoryOptions(list, undefined)).toEqual([]);
    });
});

describe('unitOptions', () => {
    it('labels units "Name (code)" like before the move to the database', () => {
        const options = unitOptions(referenceUnits);

        expect(options).toHaveLength(13);
        expect(options[0]).toEqual({ value: 'm', label: 'Metre (m)' });
        expect(options).toContainEqual({ value: 'pc', label: 'Piece (pc)' });
        expect(unitLabel(unit('sqm'))).toBe('Square metre (sqm)');
    });

    it('hides archived units except the one in use', () => {
        const list = [unit('m'), archived(unit('cm')), archived(unit('yd'))];

        expect(unitOptions(list).map((option) => option.value)).toEqual(['m']);
        expect(unitOptions(list, 'yd').map((option) => option.value)).toEqual([
            'm',
            'yd',
        ]);
    });
});

describe('activeFirst', () => {
    it('puts archived rows last and keeps the order inside each group', () => {
        const rows = [
            { code: 'a', archived: true },
            { code: 'b', archived: false },
            { code: 'c', archived: true },
            { code: 'd', archived: false },
        ];

        expect(activeFirst(rows).map((row) => row.code)).toEqual([
            'b',
            'd',
            'a',
            'c',
        ]);
    });
});

describe('describeFactor', () => {
    it('trims the scale-12 string and names the base unit', () => {
        expect(describeFactor(unit('yd'))).toBe('1 yd = 0.9144 m');
        expect(describeFactor(unit('cm'))).toBe('1 cm = 0.01 m');
        expect(describeFactor(unit('sqft'))).toBe('1 sqft = 0.09290304 sqm');
        expect(describeFactor(unit('g'))).toBe('1 g = 0.001 kg');
        expect(describeFactor(unit('ml'))).toBe('1 ml = 0.001 l');
        expect(describeFactor(unit('pair'))).toBe('1 pair = 2 pc');
        expect(describeFactor(unit('m'))).toBe('1 m = 1 m');
    });

    it('never rounds through a number', () => {
        expect(
            describeFactor({
                code: 'x',
                dimension: 'length',
                factor_to_base: '0.123456789012',
            }),
        ).toBe('1 x = 0.123456789012 m');
    });

    it('says the factor varies for package units', () => {
        expect(describeFactor(unit('roll'))).toBe('Varies per material');
    });

    it('shows a dash when a non-package unit has no factor', () => {
        expect(
            describeFactor({
                code: 'x',
                dimension: 'mass',
                factor_to_base: null,
            }),
        ).toBe('—');
    });
});

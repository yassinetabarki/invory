/**
 * The 20 default colours of a workshop (INV-1), as Settings › Colours receives
 * them: in list order, with how many products use each.
 */
import type { ColourRow } from '@/types/domain';

const DEFAULTS: [name: string, code: string, swatch: string][] = [
    ['White', 'WHT', '#FFFFFF'],
    ['Ecru', 'ECR', '#EFE6D2'],
    ['Beige', 'BEI', '#D8C3A0'],
    ['Camel', 'CAM', '#B98A57'],
    ['Brown', 'BRN', '#6B4A32'],
    ['Grey', 'GRY', '#8C8C8C'],
    ['Charcoal', 'CHA', '#3D3D42'],
    ['Black', 'BLK', '#1F1F23'],
    ['Navy', 'NAV', '#1F2A44'],
    ['Indigo', 'IND', '#3E4C7E'],
    ['Light wash', 'LWH', '#9DB3D1'],
    ['Lilac', 'LIL', '#B3A2D3'],
    ['Pink', 'PNK', '#E8A9B8'],
    ['Red', 'RED', '#B8282E'],
    ['Burgundy', 'BUR', '#6E1F2E'],
    ['Rust', 'RST', '#B5552B'],
    ['Mustard', 'MUS', '#D4A12A'],
    ['Khaki', 'KHA', '#A69F74'],
    ['Olive', 'OLV', '#5F6B3A'],
    ['Forest green', 'FGR', '#2F5A43'],
];

const USAGE: Record<string, number> = {
    White: 2,
    Black: 4,
    Indigo: 3,
    'Light wash': 1,
};

export const defaultColours: ColourRow[] = DEFAULTS.map(
    ([name, code, swatch], index) => ({
        id: index + 1,
        code,
        name,
        swatch,
        position: index + 1,
        usage_count: USAGE[name] ?? 0,
    }),
);

/** One default colour by name ("Indigo"). */
export function colourNamed(name: string): ColourRow {
    const found = defaultColours.find((colour) => colour.name === name);

    if (!found) {
        throw new Error(`No default colour named ${name}`);
    }

    return found;
}

/** The 20 defaults followed by extra colours, `count` in all (for the "40 fit in one list" case). */
export function manyColours(count = 40): ColourRow[] {
    return [
        ...defaultColours,
        ...Array.from({ length: Math.max(0, count - 20) }, (_, index) => {
            const number = index + 21;

            return {
                id: number,
                code: `X${String(number).padStart(2, '0')}`,
                name: `Custom ${number}`,
                swatch: '#808080',
                position: number,
                usage_count: 0,
            };
        }),
    ].slice(0, count);
}

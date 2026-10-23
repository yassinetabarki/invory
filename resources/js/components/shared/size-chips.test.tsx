import { render, screen } from '@testing-library/react';
import { beforeEach, describe, expect, it, vi } from 'vite-plus/test';
import { SizeChips } from '@/components/shared/size-chips';
import type { SizeCount } from '@/lib/sizes';
import { resetInertiaMock } from '@/test/inertia';

vi.mock('@inertiajs/react', async (importOriginal) => {
    const { createInertiaModule } = await import('@/test/inertia');

    return createInertiaModule(
        await importOriginal<typeof import('@inertiajs/react')>(),
    );
});

const letters: SizeCount[] = [
    { label: 'S', units: 10 },
    { label: 'M', units: 20 },
    { label: 'L', units: 15 },
    { label: 'XL', units: 5 },
];

describe('<SizeChips>', () => {
    beforeEach(() => {
        resetInertiaMock();
    });

    it.each([
        {
            name: 'letter sizes',
            sizes: letters,
            keepZeros: false,
            chips: [
                ['S', '10'],
                ['M', '20'],
                ['L', '15'],
                ['XL', '5'],
            ],
            spoken: 'S 10, M 20, L 15, XL 5',
            greyed: [],
        },
        {
            name: 'a shoe size: bare label in the chip, colon when spoken',
            sizes: [{ label: '36', units: 4 }],
            keepZeros: false,
            chips: [['36', '4']],
            spoken: '36: 4',
            greyed: [],
        },
        {
            name: 'a size at 0, left out',
            sizes: [
                { label: 'S', units: 10 },
                { label: 'M', units: 0 },
            ],
            keepZeros: false,
            chips: [['S', '10']],
            spoken: 'S 10',
            greyed: [],
        },
        {
            name: 'a size at 0, kept and greyed',
            sizes: [
                { label: 'S', units: 10 },
                { label: 'M', units: 0 },
            ],
            keepZeros: true,
            chips: [
                ['S', '10'],
                ['M', '0'],
            ],
            spoken: 'S 10, M 0',
            greyed: ['M'],
        },
    ])('shows $name', ({ sizes, keepZeros, chips, spoken, greyed }) => {
        const { container } = render(
            <SizeChips sizes={sizes} keepZeros={keepZeros} />,
        );

        const row = container.querySelector('[aria-hidden="true"]');

        expect(row).toHaveClass('flex-wrap');
        expect(
            Array.from(row?.children ?? []).map((chip) => [
                chip.children[0].textContent,
                chip.children[1].textContent,
            ]),
        ).toEqual(chips);
        expect(
            Array.from(row?.children ?? [])
                .filter((chip) => chip.hasAttribute('data-zero'))
                .map((chip) => chip.children[0].textContent),
        ).toEqual(greyed);

        // The words are heard once, on one hidden line.
        expect(screen.getAllByText(spoken)).toHaveLength(1);
        expect(screen.getByText(spoken)).toHaveClass('sr-only');
    });
});

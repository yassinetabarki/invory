import { render, screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { useState } from 'react';
import type { ComponentProps } from 'react';
import { describe, expect, it } from 'vite-plus/test';
import { SizesField } from '@/components/products/sizes-field';
import { fullSizeOptionsFixture } from '@/test/fixtures/products';

type Props = Partial<ComponentProps<typeof SizesField>>;

function Harness({ value: initial = [], ...props }: Props) {
    const [value, setValue] = useState(initial);

    return (
        <SizesField
            id="f"
            sizes={fullSizeOptionsFixture}
            hint="A hint."
            {...props}
            value={value}
            onChange={setValue}
        />
    );
}

function setup(props: Props = {}) {
    const user = userEvent.setup();

    render(<Harness {...props} />);

    return {
        user,
        size: (name: string) => screen.getByRole('button', { name }),
    };
}

const S_TO_XL = [1, 2, 3, 4];
const ONE_SIZE = 16;

describe('<SizesField>', () => {
    it('lets One size and the other sizes exclude each other, both ways', async () => {
        const { user, size } = setup({ value: S_TO_XL });

        expect(screen.getByText('4 sizes chosen')).toBeInTheDocument();

        await user.click(size('One size'));

        expect(size('One size')).toHaveAttribute('aria-pressed', 'true');
        expect(size('S')).toHaveAttribute('aria-pressed', 'false');
        expect(screen.getByText('1 size chosen')).toBeInTheDocument();

        await user.click(size('M'));

        expect(size('One size')).toHaveAttribute('aria-pressed', 'false');
        expect(size('M')).toHaveAttribute('aria-pressed', 'true');
    });

    it('keeps a locked size pressed, reachable and explained, with the lock sentence once', async () => {
        const reason =
            "Can't be taken off: it has units in stock, is on orders and has barcodes.";
        const { user, size } = setup({
            value: S_TO_XL,
            locks: { 4: reason },
            oneSizeLock: "XL is in use, so One size can't be chosen.",
        });

        await user.click(size('XL'));

        expect(size('XL')).toHaveAttribute('aria-pressed', 'true');
        expect(size('XL')).toHaveAttribute('aria-disabled', 'true');
        expect(size('XL')).not.toBeDisabled();
        expect(size('XL')).toHaveAccessibleDescription(reason);
        expect(
            screen.getAllByText(
                /^Sizes with a lock are in use by this product/,
            ),
        ).toHaveLength(1);

        // A size that isn't in use still comes off.
        await user.click(size('S'));

        expect(size('S')).toHaveAttribute('aria-pressed', 'false');

        // One size can't be chosen while XL is in use.
        await user.click(size('One size'));

        expect(size('One size')).toHaveAttribute('aria-pressed', 'false');
        expect(size('One size')).toHaveAccessibleDescription(
            "XL is in use, so One size can't be chosen.",
        );
    });

    it('lets no other size be chosen while One size is in use (Leather Tote)', async () => {
        const reason = "One size is in use, so other sizes can't be chosen.";
        const { user, size } = setup({
            value: [ONE_SIZE],
            locks: { [ONE_SIZE]: "Can't be taken off: it has units in stock." },
            otherSizesLock: reason,
        });

        await user.click(size('M'));

        expect(size('M')).toHaveAttribute('aria-pressed', 'false');
        expect(size('M')).toHaveAttribute('aria-disabled', 'true');
        expect(size('M')).toHaveAccessibleDescription(reason);
        expect(size('One size')).toHaveAttribute('aria-pressed', 'true');
        expect(screen.getByText('1 size chosen')).toBeInTheDocument();
    });

    it('announces the error with the first size button, and keeps its own id for the anchor', () => {
        const { container } = render(
            <Harness
                error={['Choose at least one size.']}
                anchorId="suggested-sizes"
            />,
        );
        const first = screen.getByRole('button', { name: 'XS' });

        expect(first).toHaveAttribute('aria-describedby', 'f-error-0');
        expect(first).toHaveAccessibleDescription('Choose at least one size.');
        expect(screen.getByRole('button', { name: 'S' })).not.toHaveAttribute(
            'aria-describedby',
        );
        expect(container.querySelector('#suggested-sizes')).not.toBeNull();
        expect(container.querySelector('#sizes')).toBeNull();
    });

    it('is one tab stop: arrow keys move along the sizes and Space presses', async () => {
        const { user, size } = setup();

        await user.tab();

        expect(size('XS')).toHaveFocus();

        await user.keyboard('{ArrowRight}');

        expect(size('S')).toHaveFocus();

        await user.keyboard(' ');

        expect(size('S')).toHaveAttribute('aria-pressed', 'true');

        await user.keyboard('{ArrowLeft}');

        expect(size('XS')).toHaveFocus();

        // One tab stop: Tab leaves the group.
        await user.tab();

        expect(
            screen
                .queryAllByRole('button')
                .includes(document.activeElement as HTMLElement),
        ).toBe(false);
    });
});

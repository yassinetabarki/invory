import { render, screen, within } from '@testing-library/react';
import { describe, expect, it } from 'vite-plus/test';
import { ProductHeader } from '@/components/products/product-header';
import { ProductPhoto } from '@/components/products/product-photo';
import {
    canvasLowSneaker,
    choreJacket,
    denimJacket,
    sizeListOneSize,
} from '@/test/fixtures/products';

describe('<ProductHeader>', () => {
    it('shows the name as the page title with SKU, category and status', () => {
        render(<ProductHeader product={denimJacket} />);

        expect(
            screen.getByRole('heading', { level: 1, name: 'Denim Jacket' }),
        ).toBeInTheDocument();
        expect(screen.getByText('PRD-JKT-001')).toHaveClass('font-mono');
        expect(screen.getByText('Apparel')).toBeInTheDocument();
        expect(screen.getByText('Active')).toBeInTheDocument();
    });

    it.each([
        ['Denim Jacket', denimJacket, 'Apparel', 'Sizes S, M, L, XL'],
        ['Canvas Low Sneaker', canvasLowSneaker, 'Footwear', 'Sizes 36 to 46'],
        [
            'Leather Tote',
            {
                ...denimJacket,
                name: 'Leather Tote',
                category_label: 'Bag',
                size_list: sizeListOneSize,
            },
            'Bag',
            'One size',
        ],
    ])(
        'names the sizes of %s next to its category',
        (_, product, category, sizes) => {
            render(<ProductHeader product={product} />);

            const row = screen.getByText(category).parentElement;

            expect(
                within(row as HTMLElement).getByText(sizes),
            ).toBeInTheDocument();
        },
    );

    it('labels a draft in words', () => {
        render(<ProductHeader product={choreJacket} />);

        expect(screen.getByText('Draft')).toBeInTheDocument();
    });

    it('renders the actions it is given', () => {
        render(
            <ProductHeader
                product={denimJacket}
                actions={<button type="button">Edit</button>}
            />,
        );

        expect(
            screen.getByRole('button', { name: 'Edit' }),
        ).toBeInTheDocument();
    });
});

describe('<ProductPhoto>', () => {
    it('shows the photo with the description as its caption', () => {
        render(
            <ProductPhoto
                name="Denim Jacket"
                photoUrl="/mock/photos/prd-jkt-001.svg"
                description="Classic trucker jacket."
            />,
        );

        expect(
            screen.getByRole('img', { name: 'Photo of Denim Jacket' }),
        ).toHaveAttribute('src', '/mock/photos/prd-jkt-001.svg');
        expect(screen.getByText('Classic trucker jacket.')).toBeInTheDocument();
    });

    it('shows a placeholder and no caption without a photo or description', () => {
        const { container } = render(
            <ProductPhoto
                name="Canvas Chore Jacket"
                photoUrl={null}
                description={null}
            />,
        );

        expect(screen.queryByRole('img', { name: /Photo of/ })).toBeNull();
        expect(container.querySelector('figcaption')).toBeNull();
        expect(container.querySelector('figure')).toBeInTheDocument();
    });
});

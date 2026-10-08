import { render, screen } from '@testing-library/react';
import { describe, expect, it } from 'vite-plus/test';
import { PhotoThumb } from '@/components/shared/photo-thumb';

describe('<PhotoThumb>', () => {
    describe('with a photo', () => {
        it('renders the image with its alt text, cropped to the box', () => {
            render(
                <PhotoThumb
                    src="/storage/jacket.webp"
                    alt="Photo of Denim Jacket"
                    size="md"
                />,
            );

            const image = screen.getByRole('img', {
                name: 'Photo of Denim Jacket',
            });

            expect(image).toHaveAttribute('src', '/storage/jacket.webp');
            expect(image).toHaveClass('object-cover', 'rounded-md');
        });

        it('is decorative when the alt text is empty (name sits next to it)', () => {
            const { container } = render(
                <PhotoThumb src="/storage/jacket.webp" alt="" size="sm" />,
            );

            expect(screen.queryByRole('img')).not.toBeInTheDocument();
            expect(container.querySelector('img')).toHaveAttribute('alt', '');
        });

        it.each([
            ['sm', 'w-9'],
            ['md', 'w-10'],
            ['lg', 'w-full'],
        ] as const)('sizes the %s thumbnail with %s', (size, widthClass) => {
            const { container } = render(
                <PhotoThumb src="/a.png" alt="" size={size} />,
            );

            expect(container.querySelector('img')).toHaveClass(widthClass);
        });

        it('is square by default and 4:5 for portrait', () => {
            const { container, rerender } = render(
                <PhotoThumb src="/a.png" alt="" size="md" />,
            );

            expect(container.querySelector('img')).toHaveClass('aspect-square');

            rerender(
                <PhotoThumb src="/a.png" alt="" size="md" shape="portrait" />,
            );

            expect(container.querySelector('img')).toHaveClass('aspect-[4/5]');
        });

        it('accepts an extra class name', () => {
            const { container } = render(
                <PhotoThumb src="/a.png" alt="" size="md" className="ring-2" />,
            );

            expect(container.querySelector('img')).toHaveClass('ring-2');
        });
    });

    describe('without a photo', () => {
        it('renders a neutral tile with a decorative image icon, and no <img>', () => {
            const { container } = render(
                <PhotoThumb src={null} alt="Photo of Denim Jacket" size="md" />,
            );

            const tile = container.querySelector('[data-empty="true"]');

            expect(tile).toBeInTheDocument();
            expect(tile).toHaveClass('bg-muted', 'rounded-md');
            expect(container.querySelector('img')).not.toBeInTheDocument();
            expect(screen.queryByRole('img')).not.toBeInTheDocument();
            expect(tile?.querySelector('svg')).toHaveAttribute(
                'aria-hidden',
                'true',
            );
        });

        it('keeps the same width and shape as a photo of that size', () => {
            const { container } = render(
                <PhotoThumb src={null} alt="" size="sm" shape="portrait" />,
            );

            expect(container.querySelector('[data-empty="true"]')).toHaveClass(
                'w-9',
                'aspect-[4/5]',
            );
        });

        it('scales the icon with the size', () => {
            const { container, rerender } = render(
                <PhotoThumb src={null} alt="" size="sm" />,
            );

            expect(container.querySelector('svg')).toHaveClass('size-4');

            rerender(<PhotoThumb src={null} alt="" size="lg" />);

            expect(container.querySelector('svg')).toHaveClass('size-10');
        });
    });
});

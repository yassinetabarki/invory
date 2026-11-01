import { render, screen } from '@testing-library/react';
import { describe, expect, it } from 'vite-plus/test';
import { Barcode } from '@/components/shared/barcode';

describe('Barcode', () => {
    it('draws 30 bars and the 13 digits as text, and nothing for an invalid number', () => {
        const { container } = render(<Barcode value="2000000000015" />);

        expect(container.querySelector('svg')).toHaveAttribute(
            'aria-hidden',
            'true',
        );
        expect(container.querySelectorAll('svg rect')).toHaveLength(30);
        expect(screen.getByText('2000000000015')).toBeVisible();

        const invalid = render(<Barcode value="2000000000016" />);

        expect(invalid.container).toBeEmptyDOMElement();
    });
});

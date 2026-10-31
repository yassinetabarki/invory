import { render, screen } from '@testing-library/react';
import { describe, expect, it } from 'vite-plus/test';
import { ColourTag } from '@/components/shared/colour-tag';

describe('ColourTag', () => {
    it('reads the name and hides the swatch, which carries the colour', () => {
        const { container } = render(
            <ColourTag colour={{ name: 'Indigo', swatch: '#3E4C7E' }} />,
        );

        expect(screen.getByText('Indigo')).toBeInTheDocument();

        const swatch = container.querySelector('[data-slot="colour-swatch"]');

        expect(swatch).toHaveAttribute('aria-hidden', 'true');
        expect(swatch).toHaveStyle({ backgroundColor: '#3E4C7E' });
    });
});

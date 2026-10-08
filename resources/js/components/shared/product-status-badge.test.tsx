import { render, screen } from '@testing-library/react';
import { describe, expect, it } from 'vite-plus/test';
import { ProductStatusBadge } from '@/components/shared/product-status-badge';
import type { ProductStatus } from '@/types/domain';

describe('<ProductStatusBadge>', () => {
    it.each<[ProductStatus, string]>([
        ['draft', 'Draft'],
        ['active', 'Active'],
        ['archived', 'Archived'],
    ])('shows the server label for %s', (status, label) => {
        render(<ProductStatusBadge status={status} label={label} />);

        expect(screen.getByText(label)).toBeInTheDocument();
    });

    it('shows the label it is given, not one of its own', () => {
        render(<ProductStatusBadge status="active" label="En vente" />);

        expect(screen.getByText('En vente')).toBeInTheDocument();
        expect(screen.queryByText('Active')).not.toBeInTheDocument();
    });

    it('makes a draft stand out with the info tone', () => {
        render(<ProductStatusBadge status="draft" label="Draft" />);

        expect(screen.getByText('Draft')).toHaveClass(
            'bg-info',
            'text-info-foreground',
        );
    });

    it.each<[ProductStatus, string]>([
        ['active', 'Active'],
        ['archived', 'Archived'],
    ])('keeps %s neutral', (status, label) => {
        render(<ProductStatusBadge status={status} label={label} />);

        expect(screen.getByText(label)).toHaveClass(
            'bg-neutral',
            'text-neutral-foreground',
        );
    });

    it('marks the status on the element for styling and tests', () => {
        render(<ProductStatusBadge status="archived" label="Archived" />);

        expect(screen.getByText('Archived')).toHaveAttribute(
            'data-status',
            'archived',
        );
    });

    it('accepts an extra class name', () => {
        render(
            <ProductStatusBadge
                status="draft"
                label="Draft"
                className="ml-2"
            />,
        );

        expect(screen.getByText('Draft')).toHaveClass('ml-2');
    });
});

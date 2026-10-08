import { render, screen } from '@testing-library/react';
import { describe, expect, it } from 'vite-plus/test';
import { PageHeader } from '@/components/shared/page-header';

describe('<PageHeader>', () => {
    it('renders the title as the page heading', () => {
        render(<PageHeader title="Materials" />);

        expect(
            screen.getByRole('heading', { level: 1, name: 'Materials' }),
        ).toBeInTheDocument();
    });

    it('renders the description and actions when given', () => {
        render(
            <PageHeader
                title="Materials"
                description="Everything in stock"
                actions={<button type="button">Add material</button>}
            />,
        );

        expect(screen.getByText('Everything in stock')).toBeInTheDocument();
        expect(
            screen.getByRole('button', { name: 'Add material' }),
        ).toBeInTheDocument();
    });

    it('omits the description and actions when not given', () => {
        render(<PageHeader title="Materials" />);

        expect(screen.queryByRole('button')).not.toBeInTheDocument();
        expect(screen.getAllByText(/./)).toHaveLength(1);
    });
});

import { render, screen } from '@testing-library/react';
import { Boxes } from 'lucide-react';
import { describe, expect, it } from 'vite-plus/test';
import { EmptyState } from '@/components/shared/empty-state';

describe('<EmptyState>', () => {
    it('shows the title, description and action', () => {
        render(
            <EmptyState
                icon={Boxes}
                title="No materials yet"
                description="Add your first material to start costing."
                action={<button type="button">Add your first material</button>}
            />,
        );

        expect(
            screen.getByRole('heading', { name: 'No materials yet' }),
        ).toBeInTheDocument();
        expect(
            screen.getByText('Add your first material to start costing.'),
        ).toBeInTheDocument();
        expect(
            screen.getByRole('button', { name: 'Add your first material' }),
        ).toBeInTheDocument();
    });

    it('works with only a title', () => {
        render(<EmptyState title="Nothing here" />);

        expect(
            screen.getByRole('heading', { name: 'Nothing here' }),
        ).toBeInTheDocument();
        expect(screen.queryByRole('button')).not.toBeInTheDocument();
    });
});

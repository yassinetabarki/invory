import { render, screen } from '@testing-library/react';
import { beforeEach, describe, expect, it, vi } from 'vite-plus/test';
import { MaterialPhotoCard } from '@/components/materials/material-photo-card';
import { resetInertiaMock } from '@/test/inertia';

vi.mock('@inertiajs/react', async (importOriginal) => {
    const { createInertiaModule } = await import('@/test/inertia');

    return createInertiaModule(
        await importOriginal<typeof import('@inertiajs/react')>(),
    );
});

describe('<MaterialPhotoCard>', () => {
    beforeEach(() => {
        resetInertiaMock();
    });

    it('shows the photo with a description and offers to replace it on the edit page', () => {
        render(
            <MaterialPhotoCard
                name="Metal shank button 17mm"
                photoUrl="/mock/photos/mat-btn-017.svg"
                editHref="/materials/3/edit"
            />,
        );

        expect(
            screen.getByRole('img', {
                name: 'Photo of Metal shank button 17mm',
            }),
        ).toHaveAttribute('src', '/mock/photos/mat-btn-017.svg');
        expect(
            screen.getByRole('link', { name: 'Replace photo' }),
        ).toHaveAttribute('href', '/materials/3/edit');
        expect(
            screen.getByText('JPG, PNG or WebP · max 4 MB'),
        ).toBeInTheDocument();
    });

    it('shows a placeholder and offers to add a photo when there is none', () => {
        render(
            <MaterialPhotoCard
                name="Denim 12oz Indigo"
                photoUrl={null}
                editHref="/materials/1/edit"
            />,
        );

        expect(screen.queryByRole('img')).not.toBeInTheDocument();
        expect(screen.getByRole('link', { name: 'Add photo' })).toHaveAttribute(
            'href',
            '/materials/1/edit',
        );
    });
});

import { Form } from '@inertiajs/react';
import { createEvent, fireEvent, render, screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import {
    afterEach,
    beforeEach,
    describe,
    expect,
    it,
    vi,
} from 'vite-plus/test';
import { PhotoField } from '@/components/shared/photo-field';
import { inertiaMock, resetInertiaMock } from '@/test/inertia';

vi.mock('@inertiajs/react', async (importOriginal) => {
    const { createInertiaModule } = await import('@/test/inertia');

    return createInertiaModule(
        await importOriginal<typeof import('@inertiajs/react')>(),
    );
});

const png = new File(['png'], 'jacket.png', { type: 'image/png' });
const webp = new File(['webp'], 'jacket-2.webp', { type: 'image/webp' });

function bigFile(): File {
    const file = new File(['x'], 'huge.jpg', { type: 'image/jpeg' });

    Object.defineProperty(file, 'size', { value: 4 * 1024 * 1024 + 1 });

    return file;
}

function fileInput(): HTMLInputElement {
    // The field's label names the native file input.
    const input = screen.getByLabelText('Photo');

    if (!(input instanceof HTMLInputElement)) {
        throw new Error('Expected the photo label to name the file input.');
    }

    return input;
}

function fieldRoot(container: HTMLElement): HTMLElement {
    const field = container.querySelector<HTMLElement>(
        '[data-slot="photo-field"]',
    );

    if (!field) {
        throw new Error('Expected the photo field root.');
    }

    return field;
}

function hiddenRemoveInput(container: HTMLElement): HTMLInputElement {
    const input = container.querySelector<HTMLInputElement>(
        'input[type="hidden"]',
    );

    if (!input) {
        throw new Error('Expected the hidden remove input.');
    }

    return input;
}

describe('<PhotoField>', () => {
    let createObjectURL: ReturnType<typeof vi.spyOn>;
    let revokeObjectURL: ReturnType<typeof vi.spyOn>;
    let urlCount: number;

    beforeEach(() => {
        resetInertiaMock();
        urlCount = 0;
        createObjectURL = vi
            .spyOn(URL, 'createObjectURL')
            .mockImplementation(() => `blob:preview-${++urlCount}`);
        revokeObjectURL = vi
            .spyOn(URL, 'revokeObjectURL')
            .mockImplementation(() => undefined);
    });

    afterEach(() => {
        vi.restoreAllMocks();
    });

    describe('without a photo', () => {
        it('shows a drop zone with a browse link and the file hint', () => {
            render(
                <PhotoField label="Photo" currentUrl={null} aspect="square" />,
            );

            expect(
                screen.getByText(/drop a photo here or/i),
            ).toBeInTheDocument();
            expect(screen.getByText('browse')).toBeInTheDocument();
            expect(
                screen.getByText('JPG, PNG or WebP · max 4 MB'),
            ).toBeInTheDocument();
            expect(
                screen.queryByRole('button', { name: /remove photo/i }),
            ).not.toBeInTheDocument();
        });

        it('uses a native file input named photo that only accepts JPG, PNG and WebP', () => {
            render(
                <PhotoField label="Photo" currentUrl={null} aspect="square" />,
            );

            const input = fileInput();

            expect(input).toHaveAttribute('type', 'file');
            expect(input).toHaveAttribute('name', 'photo');
            expect(input).toHaveAttribute(
                'accept',
                'image/jpeg,image/png,image/webp',
            );
        });

        it('carries a hidden remove_photo input that starts at 0', () => {
            const { container } = render(
                <PhotoField label="Photo" currentUrl={null} aspect="square" />,
            );

            const hidden = hiddenRemoveInput(container);

            expect(hidden).toHaveAttribute('name', 'remove_photo');
            expect(hidden).toHaveValue('0');
        });

        it('accepts other input names', () => {
            const { container } = render(
                <PhotoField
                    label="Photo"
                    name="image"
                    removeName="remove_image"
                    currentUrl={null}
                    aspect="square"
                />,
            );

            expect(fileInput()).toHaveAttribute('name', 'image');
            expect(hiddenRemoveInput(container)).toHaveAttribute(
                'name',
                'remove_image',
            );
        });

        it('shows the label, which names the file input', () => {
            render(
                <PhotoField
                    label="Product photo"
                    currentUrl={null}
                    aspect="square"
                />,
            );

            const input = screen.getByLabelText('Product photo');

            expect(input).toHaveAttribute('type', 'file');
            expect(screen.getByText('Product photo')).toBeInTheDocument();
        });

        it('sizes the drop zone to the aspect: square or portrait', () => {
            const { rerender } = render(
                <PhotoField label="Photo" currentUrl={null} aspect="square" />,
            );

            expect(
                screen.getByText(/drop a photo here/i).closest('label'),
            ).toHaveClass('aspect-square');

            rerender(
                <PhotoField
                    label="Photo"
                    currentUrl={null}
                    aspect="portrait"
                />,
            );

            expect(
                screen.getByText(/drop a photo here/i).closest('label'),
            ).toHaveClass('aspect-[4/5]');
        });

        it('is reachable and operable from the keyboard (the file input takes focus)', async () => {
            const user = userEvent.setup();

            render(
                <PhotoField label="Photo" currentUrl={null} aspect="square" />,
            );

            await user.tab();

            expect(fileInput()).toHaveFocus();
        });
    });

    describe('picking a file', () => {
        it('previews the picked file and tells the form about it', async () => {
            const user = userEvent.setup();
            const onChange = vi.fn();

            const { container } = render(
                <PhotoField
                    label="Photo"
                    currentUrl={null}
                    aspect="square"
                    onChange={onChange}
                />,
            );

            await user.upload(fileInput(), png);

            expect(createObjectURL).toHaveBeenCalledWith(png);
            expect(container.querySelector('img')).toHaveAttribute(
                'src',
                'blob:preview-1',
            );
            expect(
                screen.getByAltText('New photo preview'),
            ).toBeInTheDocument();
            expect(screen.getByText('jacket.png')).toBeInTheDocument();
            expect(onChange).toHaveBeenCalledExactlyOnceWith(png);
            expect(fileInput().files?.item(0)).toBe(png);
            expect(
                screen.queryByText(/drop a photo here/i),
            ).not.toBeInTheDocument();
        });

        it('offers Replace and Remove once there is a photo', async () => {
            const user = userEvent.setup();

            render(
                <PhotoField label="Photo" currentUrl={null} aspect="square" />,
            );

            await user.upload(fileInput(), png);

            expect(screen.getByText('Replace photo')).toBeInTheDocument();
            expect(
                screen.getByRole('button', { name: 'Remove photo' }),
            ).toBeInTheDocument();
        });

        it('frees the previous preview when another file replaces it, and when unmounted', async () => {
            const user = userEvent.setup();

            const { unmount } = render(
                <PhotoField label="Photo" currentUrl={null} aspect="square" />,
            );

            await user.upload(fileInput(), png);
            expect(revokeObjectURL).not.toHaveBeenCalled();

            await user.upload(fileInput(), webp);
            expect(revokeObjectURL).toHaveBeenCalledExactlyOnceWith(
                'blob:preview-1',
            );
            expect(screen.getByText('jacket-2.webp')).toBeInTheDocument();

            unmount();
            expect(revokeObjectURL).toHaveBeenLastCalledWith('blob:preview-2');
        });

        it('treats a cancelled picker (no file) as nothing selected', async () => {
            const user = userEvent.setup();
            const onChange = vi.fn();

            render(
                <PhotoField
                    label="Photo"
                    currentUrl={null}
                    aspect="square"
                    onChange={onChange}
                />,
            );

            await user.upload(fileInput(), png);
            await user.upload(fileInput(), []);

            expect(onChange).toHaveBeenLastCalledWith(null);
            expect(screen.getByText(/drop a photo here/i)).toBeInTheDocument();
        });
    });

    describe('client pre-check', () => {
        it('rejects a file that is not JPG, PNG or WebP, and clears the input', async () => {
            const user = userEvent.setup({ applyAccept: false });
            const onChange = vi.fn();
            const svg = new File(['<svg/>'], 'logo.svg', {
                type: 'image/svg+xml',
            });

            render(
                <PhotoField
                    label="Photo"
                    currentUrl={null}
                    aspect="square"
                    onChange={onChange}
                />,
            );

            await user.upload(fileInput(), svg);

            expect(screen.getByRole('alert')).toHaveTextContent(
                'Choose a JPG, PNG or WebP image.',
            );
            expect(fileInput().files).toHaveLength(0);
            expect(createObjectURL).not.toHaveBeenCalled();
            expect(onChange).toHaveBeenLastCalledWith(null);
            expect(screen.getByText(/drop a photo here/i)).toBeInTheDocument();
        });

        it('rejects a file over 4 MB', async () => {
            const user = userEvent.setup();

            render(
                <PhotoField label="Photo" currentUrl={null} aspect="square" />,
            );

            await user.upload(fileInput(), bigFile());

            expect(screen.getByRole('alert')).toHaveTextContent(
                'The photo must be 4 MB or smaller.',
            );
            expect(fileInput().files).toHaveLength(0);
        });

        it('accepts a file of exactly 4 MB', async () => {
            const user = userEvent.setup();
            const exact = new File(['x'], 'exact.png', { type: 'image/png' });

            Object.defineProperty(exact, 'size', { value: 4 * 1024 * 1024 });

            render(
                <PhotoField label="Photo" currentUrl={null} aspect="square" />,
            );

            await user.upload(fileInput(), exact);

            expect(screen.queryByRole('alert')).not.toBeInTheDocument();
            expect(screen.getByText('exact.png')).toBeInTheDocument();
        });

        it('clears the message when a valid file follows', async () => {
            const user = userEvent.setup();

            render(
                <PhotoField label="Photo" currentUrl={null} aspect="square" />,
            );

            await user.upload(fileInput(), bigFile());
            expect(screen.getByRole('alert')).toBeInTheDocument();

            await user.upload(fileInput(), png);
            expect(screen.queryByRole('alert')).not.toBeInTheDocument();
        });
    });

    describe('errors', () => {
        it('links the server error to the input and marks it invalid', () => {
            render(
                <PhotoField
                    label="Photo"
                    currentUrl={null}
                    aspect="square"
                    error="The photo field must not be greater than 4096 kilobytes."
                />,
            );

            const error = screen.getByRole('alert');
            const input = fileInput();

            expect(error).toHaveTextContent(/4096 kilobytes/);
            expect(input).toHaveAttribute('aria-invalid', 'true');
            expect(input.getAttribute('aria-describedby')).toContain(error.id);
        });

        it('keeps the hint in the description next to the error', () => {
            render(
                <PhotoField
                    label="Photo"
                    currentUrl={null}
                    aspect="square"
                    error="Bad photo."
                />,
            );

            const hint = screen.getByText('JPG, PNG or WebP · max 4 MB');

            expect(fileInput().getAttribute('aria-describedby')).toContain(
                hint.id,
            );
        });

        it('is valid and described by the hint alone when there is no error', () => {
            render(
                <PhotoField label="Photo" currentUrl={null} aspect="square" />,
            );

            const hint = screen.getByText('JPG, PNG or WebP · max 4 MB');

            expect(fileInput()).not.toHaveAttribute('aria-invalid');
            expect(fileInput()).toHaveAttribute('aria-describedby', hint.id);
        });

        it('shows the client message in place of the server one', async () => {
            const user = userEvent.setup();

            render(
                <PhotoField
                    label="Photo"
                    currentUrl={null}
                    aspect="square"
                    error="Server says no."
                />,
            );

            await user.upload(fileInput(), bigFile());

            expect(screen.getByRole('alert')).toHaveTextContent(
                'The photo must be 4 MB or smaller.',
            );
            expect(
                screen.queryByText('Server says no.'),
            ).not.toBeInTheDocument();
        });
    });

    describe('with an existing photo', () => {
        it('shows the current photo with Replace and Remove, and no drop zone', () => {
            render(
                <PhotoField
                    label="Photo"
                    currentUrl="/storage/jacket.webp"
                    aspect="portrait"
                />,
            );

            expect(
                screen.getByRole('img', { name: 'Current photo' }),
            ).toHaveAttribute('src', '/storage/jacket.webp');
            expect(screen.getByText('Replace photo')).toBeInTheDocument();
            expect(
                screen.getByRole('button', { name: 'Remove photo' }),
            ).toBeInTheDocument();
            expect(
                screen.queryByText(/drop a photo here/i),
            ).not.toBeInTheDocument();
        });

        it('opens the picker from Replace photo (a label for the file input)', async () => {
            const user = userEvent.setup();
            const onChange = vi.fn();

            render(
                <PhotoField
                    label="Photo"
                    currentUrl="/storage/jacket.webp"
                    aspect="square"
                    onChange={onChange}
                />,
            );

            const clicked = vi.fn();

            fileInput().addEventListener('click', clicked);
            await user.click(screen.getByText('Replace photo'));

            expect(clicked).toHaveBeenCalled();

            await user.upload(fileInput(), png);

            expect(
                screen.getByAltText('New photo preview'),
            ).toBeInTheDocument();
            expect(onChange).toHaveBeenCalledWith(png);
        });

        it('marks the photo for removal and offers to undo it', async () => {
            const user = userEvent.setup();
            const onRemoveChange = vi.fn();

            const { container } = render(
                <PhotoField
                    label="Photo"
                    currentUrl="/storage/jacket.webp"
                    aspect="square"
                    onRemoveChange={onRemoveChange}
                />,
            );

            await user.click(
                screen.getByRole('button', { name: 'Remove photo' }),
            );

            expect(hiddenRemoveInput(container)).toHaveValue('1');
            expect(onRemoveChange).toHaveBeenCalledExactlyOnceWith(true);
            expect(screen.queryByRole('img')).not.toBeInTheDocument();
            expect(
                screen.getByText(
                    /current photo will be removed when you save/i,
                ),
            ).toBeInTheDocument();
            expect(screen.getByText(/drop a photo here/i)).toBeInTheDocument();

            await user.click(
                screen.getByRole('button', { name: /keep current photo/i }),
            );

            expect(hiddenRemoveInput(container)).toHaveValue('0');
            expect(onRemoveChange).toHaveBeenLastCalledWith(false);
            expect(
                screen.getByRole('img', { name: 'Current photo' }),
            ).toBeInTheDocument();
        });

        it('cancels the removal when a new file is picked instead', async () => {
            const user = userEvent.setup();
            const onRemoveChange = vi.fn();

            const { container } = render(
                <PhotoField
                    label="Photo"
                    currentUrl="/storage/jacket.webp"
                    aspect="square"
                    onRemoveChange={onRemoveChange}
                />,
            );

            await user.click(
                screen.getByRole('button', { name: 'Remove photo' }),
            );
            await user.upload(fileInput(), png);

            expect(hiddenRemoveInput(container)).toHaveValue('0');
            expect(onRemoveChange).toHaveBeenLastCalledWith(false);
            expect(
                screen.getByAltText('New photo preview'),
            ).toBeInTheDocument();
        });

        it('Remove after picking a new file discards it and marks the current photo for removal', async () => {
            const user = userEvent.setup();
            const onChange = vi.fn();
            const onRemoveChange = vi.fn();

            const { container } = render(
                <PhotoField
                    label="Photo"
                    currentUrl="/storage/jacket.webp"
                    aspect="square"
                    onChange={onChange}
                    onRemoveChange={onRemoveChange}
                />,
            );

            await user.upload(fileInput(), png);
            await user.click(
                screen.getByRole('button', { name: 'Remove photo' }),
            );

            expect(onChange).toHaveBeenLastCalledWith(null);
            expect(onRemoveChange).toHaveBeenLastCalledWith(true);
            expect(hiddenRemoveInput(container)).toHaveValue('1');
            expect(fileInput().files).toHaveLength(0);
        });
    });

    describe('Remove on a new pick without a current photo', () => {
        it('just discards the file and sets no removal flag', async () => {
            const user = userEvent.setup();
            const onChange = vi.fn();
            const onRemoveChange = vi.fn();

            const { container } = render(
                <PhotoField
                    label="Photo"
                    currentUrl={null}
                    aspect="square"
                    onChange={onChange}
                    onRemoveChange={onRemoveChange}
                />,
            );

            await user.upload(fileInput(), png);
            await user.click(
                screen.getByRole('button', { name: 'Remove photo' }),
            );

            expect(onChange).toHaveBeenLastCalledWith(null);
            expect(onRemoveChange).not.toHaveBeenCalled();
            expect(hiddenRemoveInput(container)).toHaveValue('0');
            expect(screen.getByText(/drop a photo here/i)).toBeInTheDocument();
        });
    });

    describe('drag and drop', () => {
        function fileList(file: File) {
            return { 0: file, length: 1, item: () => file };
        }

        it('highlights the field while a file is dragged over it', () => {
            const { container } = render(
                <PhotoField label="Photo" currentUrl={null} aspect="square" />,
            );

            const field = fieldRoot(container);

            fireEvent.dragOver(field);
            expect(field).toHaveAttribute('data-dragging', 'true');

            fireEvent.dragLeave(field);
            expect(field).not.toHaveAttribute('data-dragging');
        });

        it('selects a dropped image like a picked one', () => {
            const onChange = vi.fn();

            const { container } = render(
                <PhotoField
                    label="Photo"
                    currentUrl={null}
                    aspect="square"
                    onChange={onChange}
                />,
            );

            const field = fieldRoot(container);
            const drop = createEvent.drop(field);

            Object.defineProperty(drop, 'dataTransfer', {
                value: { files: fileList(png) },
            });
            fireEvent(field, drop);

            expect(drop.defaultPrevented).toBe(true);
            expect(container.querySelector('img')).toHaveAttribute(
                'src',
                'blob:preview-1',
            );
            expect(onChange).toHaveBeenCalledExactlyOnceWith(png);
            expect(field).not.toHaveAttribute('data-dragging');
        });

        it('applies the same pre-check to a dropped file', () => {
            const { container } = render(
                <PhotoField label="Photo" currentUrl={null} aspect="square" />,
            );

            const field = fieldRoot(container);
            const drop = createEvent.drop(field);
            const pdf = new File(['%PDF'], 'sheet.pdf', {
                type: 'application/pdf',
            });

            Object.defineProperty(drop, 'dataTransfer', {
                value: { files: fileList(pdf) },
            });
            fireEvent(field, drop);

            expect(screen.getByRole('alert')).toHaveTextContent(
                'Choose a JPG, PNG or WebP image.',
            );
        });
    });

    describe('inside an Inertia <Form>', () => {
        it('submits the file and the removal flag with the form', async () => {
            const user = userEvent.setup();

            render(
                <Form action="/materials/5" method="post">
                    <PhotoField
                        label="Photo"
                        currentUrl="/storage/jacket.webp"
                        aspect="square"
                    />
                    <button type="submit">Save</button>
                </Form>,
            );

            await user.upload(fileInput(), png);
            await user.click(screen.getByRole('button', { name: 'Save' }));

            expect(inertiaMock.submissions).toEqual([
                {
                    action: '/materials/5',
                    method: 'post',
                    data: { photo: png, remove_photo: '0' },
                },
            ]);
        });

        it('submits remove_photo=1 when the current photo is removed', async () => {
            const user = userEvent.setup();

            render(
                <Form action="/materials/5" method="post">
                    <PhotoField
                        label="Photo"
                        currentUrl="/storage/jacket.webp"
                        aspect="square"
                    />
                    <button type="submit">Save</button>
                </Form>,
            );

            await user.click(
                screen.getByRole('button', { name: 'Remove photo' }),
            );
            await user.click(screen.getByRole('button', { name: 'Save' }));

            expect(inertiaMock.submissions[0]?.data).toEqual({
                photo: '',
                remove_photo: '1',
            });
        });
    });
});

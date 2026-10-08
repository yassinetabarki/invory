import { render, screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { useState } from 'react';
import { describe, expect, it } from 'vite-plus/test';
import {
    Command,
    CommandEmpty,
    CommandGroup,
    CommandInput,
    CommandItem,
    CommandList,
} from '@/components/ui/command';
import {
    Popover,
    PopoverContent,
    PopoverTrigger,
} from '@/components/ui/popover';

/** The popover's trigger button (the picker's closed state). */
function trigger(): HTMLElement {
    const element = document.querySelector<HTMLElement>(
        '[data-slot="popover-trigger"]',
    );

    if (!element) {
        throw new Error('Expected the popover trigger.');
    }

    return element;
}

const materials = [
    { id: 1, name: 'Denim 12oz Indigo' },
    { id: 2, name: 'Metal shank button 17mm' },
    { id: 3, name: 'Polyester thread' },
];

/** The searchable picker the composition editor builds from these two parts. */
function MaterialPicker({ disabledIds = [] }: { disabledIds?: number[] }) {
    const [open, setOpen] = useState(false);
    const [selected, setSelected] = useState<string | null>(null);

    return (
        <Popover open={open} onOpenChange={setOpen}>
            <PopoverTrigger asChild>
                <button
                    type="button"
                    aria-haspopup="dialog"
                    aria-expanded={open}
                >
                    {selected ?? 'Choose a material'}
                </button>
            </PopoverTrigger>
            <PopoverContent>
                <Command>
                    <CommandInput placeholder="Search materials" />
                    <CommandList>
                        <CommandEmpty>No material found.</CommandEmpty>
                        <CommandGroup>
                            {materials.map((material) => (
                                <CommandItem
                                    key={material.id}
                                    value={material.name}
                                    disabled={disabledIds.includes(material.id)}
                                    onSelect={() => {
                                        setSelected(material.name);
                                        setOpen(false);
                                    }}
                                >
                                    {material.name}
                                </CommandItem>
                            ))}
                        </CommandGroup>
                    </CommandList>
                </Command>
            </PopoverContent>
        </Popover>
    );
}

describe('Popover + Command (searchable picker)', () => {
    it('opens from the trigger and lists the options', async () => {
        const user = userEvent.setup();

        render(<MaterialPicker />);

        expect(
            screen.queryByPlaceholderText('Search materials'),
        ).not.toBeInTheDocument();

        await user.click(
            screen.getByRole('button', { name: 'Choose a material' }),
        );

        expect(
            screen.getByPlaceholderText('Search materials'),
        ).toBeInTheDocument();
        expect(screen.getAllByRole('option')).toHaveLength(3);
    });

    it('filters while typing and says when nothing matches', async () => {
        const user = userEvent.setup();

        render(<MaterialPicker />);
        await user.click(trigger());

        await user.type(screen.getByPlaceholderText('Search materials'), 'thr');

        expect(screen.getAllByRole('option')).toHaveLength(1);
        expect(
            screen.getByRole('option', { name: 'Polyester thread' }),
        ).toBeInTheDocument();

        await user.clear(screen.getByPlaceholderText('Search materials'));
        await user.type(screen.getByPlaceholderText('Search materials'), 'zzz');

        expect(screen.queryByRole('option')).not.toBeInTheDocument();
        expect(screen.getByText('No material found.')).toBeInTheDocument();
    });

    it('selects with a click and closes', async () => {
        const user = userEvent.setup();

        render(<MaterialPicker />);
        await user.click(trigger());
        await user.click(
            screen.getByRole('option', { name: 'Metal shank button 17mm' }),
        );

        expect(trigger()).toHaveTextContent('Metal shank button 17mm');
        expect(
            screen.queryByPlaceholderText('Search materials'),
        ).not.toBeInTheDocument();
    });

    it('selects with the keyboard', async () => {
        const user = userEvent.setup();

        render(<MaterialPicker />);
        await user.click(trigger());
        await user.type(
            screen.getByPlaceholderText('Search materials'),
            'denim{Enter}',
        );

        expect(trigger()).toHaveTextContent('Denim 12oz Indigo');
    });

    it('does not select a disabled option', async () => {
        const user = userEvent.setup();

        render(<MaterialPicker disabledIds={[3]} />);
        await user.click(trigger());

        const option = screen.getByRole('option', { name: 'Polyester thread' });

        expect(option).toHaveAttribute('aria-disabled', 'true');

        await user.click(option);

        expect(trigger()).toHaveTextContent('Choose a material');
    });

    it('closes with Escape', async () => {
        const user = userEvent.setup();

        render(<MaterialPicker />);
        await user.click(trigger());
        await user.keyboard('{Escape}');

        expect(
            screen.queryByPlaceholderText('Search materials'),
        ).not.toBeInTheDocument();
    });
});

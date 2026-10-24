import { render, screen } from '@testing-library/react';
import { describe, expect, it } from 'vite-plus/test';
import { MoveButton } from './move-button';

describe('MoveButton', () => {
    it.each([
        { variant: 'default', enabledClass: 'bg-primary' },
        { variant: 'outline', enabledClass: 'border-input' },
    ] as const)(
        'a $variant button is grey when disabled and keeps its look when enabled',
        ({ variant, enabledClass }) => {
            const { rerender } = render(
                <MoveButton variant={variant} disabled>
                    Move
                </MoveButton>,
            );
            const disabled = screen.getByRole('button', { name: 'Move' });
            expect(disabled).toBeDisabled();
            expect(disabled).toHaveClass(
                'disabled:bg-muted',
                'disabled:text-muted-foreground',
                'disabled:opacity-100',
            );
            expect(disabled).not.toHaveClass('disabled:opacity-50');

            rerender(<MoveButton variant={variant}>Move</MoveButton>);
            const enabled = screen.getByRole('button', { name: 'Move' });
            expect(enabled).toBeEnabled();
            expect(enabled).toHaveClass(enabledClass);
        },
    );
});

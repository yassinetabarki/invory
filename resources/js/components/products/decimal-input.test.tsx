import { render, screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { describe, expect, it, vi } from 'vite-plus/test';
import { DecimalInput } from '@/components/products/decimal-input';
import { FormField } from '@/components/products/form-field';

describe('<DecimalInput>', () => {
    it('is a plain text input with a decimal keypad, never a number input', () => {
        render(<DecimalInput aria-label="Labor" />);

        const input = screen.getByLabelText('Labor');

        expect(input).toHaveAttribute('type', 'text');
        expect(input).toHaveAttribute('inputmode', 'decimal');
        expect(input).toHaveAttribute('autocomplete', 'off');
    });

    it('keeps what is typed as text, without changing it', async () => {
        const onChange = vi.fn();
        const user = userEvent.setup();

        render(<DecimalInput aria-label="Labor" onChange={onChange} />);
        await user.type(screen.getByLabelText('Labor'), '12.50');

        expect(screen.getByLabelText('Labor')).toHaveValue('12.50');
    });

    it('announces a currency before the field and a unit after it', () => {
        render(
            <>
                <DecimalInput aria-label="Cost" addonStart="TND" />
                <DecimalInput aria-label="Quantity" addonEnd="m" />
            </>,
        );

        expect(screen.getByLabelText('Cost')).toHaveAccessibleDescription(
            'TND',
        );
        expect(screen.getByLabelText('Quantity')).toHaveAccessibleDescription(
            'm',
        );
    });

    it('does not announce an em dash (the unit is not known yet)', () => {
        render(<DecimalInput aria-label="Quantity" addonEnd="—" />);

        expect(
            screen.getByLabelText('Quantity'),
        ).not.toHaveAccessibleDescription();
        expect(screen.getByText('—')).toBeInTheDocument();
    });

    it('reads the addon first and then the caller’s own description (an error)', () => {
        render(
            <>
                <DecimalInput
                    aria-label="Quantity"
                    addonEnd="pc"
                    aria-describedby="quantity-error"
                />
                <p id="quantity-error">The quantity must be greater than 0.</p>
            </>,
        );

        expect(screen.getByLabelText('Quantity')).toHaveAccessibleDescription(
            'pc The quantity must be greater than 0.',
        );
    });
});

describe('<FormField>', () => {
    it('labels the control and marks it required', () => {
        render(
            <FormField id="sku" label="SKU" required>
                {(control) => <input {...control} />}
            </FormField>,
        );

        const input = screen.getByLabelText(/^SKU/);

        expect(input).toHaveAttribute('aria-required', 'true');
        expect(input).not.toHaveAttribute('aria-invalid');
    });

    it('says (optional) instead of the required mark', () => {
        render(
            <FormField id="note" label="Description" optional>
                {(control) => <input {...control} />}
            </FormField>,
        );

        expect(
            screen.getByLabelText('Description (optional)'),
        ).not.toHaveAttribute('aria-required');
    });

    it('links the hint and the error to the control, and marks it invalid', () => {
        render(
            <FormField
                id="price"
                label="Selling price"
                hint="Pre-fills Record a sale."
                error="The selling price must be at least 0."
            >
                {(control) => <input {...control} />}
            </FormField>,
        );

        const input = screen.getByLabelText('Selling price');

        expect(input).toHaveAttribute('aria-invalid', 'true');
        expect(input).toHaveAccessibleDescription(
            'Pre-fills Record a sale. The selling price must be at least 0.',
        );
    });
});

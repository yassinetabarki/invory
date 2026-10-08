import type { ReactNode } from 'react';
import { Label } from '@/components/ui/label';
import { cn } from '@/lib/utils';

/** Attributes every control receives so its label, hint and error are wired up. */
export type FieldControl = {
    id: string;
    'aria-invalid': true | undefined;
    'aria-required': true | undefined;
    'aria-describedby': string | undefined;
};

type FormFieldProps = {
    /** Id of the control; the hint and error ids derive from it. */
    id: string;
    label: string;
    required?: boolean;
    /** Adds a muted "(optional)" after the label. */
    optional?: boolean;
    hint?: ReactNode;
    /** Server validation error for this field. */
    error?: string;
    className?: string;
    children: (control: FieldControl) => ReactNode;
};

/**
 * A labelled form field: label (with a required asterisk or "(optional)"),
 * the control, a hint and an error. The control gets `aria-invalid` and
 * `aria-describedby` through the render prop.
 */
export function FormField({
    id,
    label,
    required = false,
    optional = false,
    hint,
    error,
    className,
    children,
}: FormFieldProps) {
    const hintId = `${id}-hint`;
    const errorId = `${id}-error`;
    const describedBy =
        [hint ? hintId : null, error ? errorId : null]
            .filter(Boolean)
            .join(' ') || undefined;

    return (
        <div className={cn('flex min-w-0 flex-col gap-2', className)}>
            <Label htmlFor={id}>
                {label}
                {required && (
                    <span
                        aria-hidden="true"
                        className="text-destructive-foreground"
                    >
                        {' '}
                        *
                    </span>
                )}
                {optional && (
                    <span className="font-normal text-muted-foreground">
                        {' '}
                        (optional)
                    </span>
                )}
            </Label>
            {children({
                id,
                'aria-invalid': error ? true : undefined,
                'aria-required': required ? true : undefined,
                'aria-describedby': describedBy,
            })}
            {hint && (
                <p id={hintId} className="text-xs text-muted-foreground">
                    {hint}
                </p>
            )}
            {error && (
                <p id={errorId} className="text-sm text-destructive-foreground">
                    {error}
                </p>
            )}
        </div>
    );
}

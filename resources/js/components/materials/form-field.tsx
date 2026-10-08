import type { ComponentProps, ReactNode } from 'react';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { cn } from '@/lib/utils';

/** Attributes every control receives so label, hint and error are wired up. */
export type ControlProps = {
    id: string;
    'aria-invalid': true | undefined;
    'aria-required': true | undefined;
    'aria-describedby': string | undefined;
};

type FormFieldProps = {
    /** Id of the control; `<id>-hint` and `<id>-error` name the texts below it. */
    id: string;
    label: string;
    required?: boolean;
    /** Adds a muted "(optional)" to the label. */
    optional?: boolean;
    hint?: string;
    error?: string;
    className?: string;
    children: (control: ControlProps) => ReactNode;
};

/**
 * A labelled field: label (with "(optional)" when it is), the control, a hint
 * and the server error. The control gets `aria-invalid` and an
 * `aria-describedby` that names the hint and the error.
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
    const describedBy =
        [hint ? `${id}-hint` : null, error ? `${id}-error` : null]
            .filter(Boolean)
            .join(' ') || undefined;

    return (
        <div className={cn('min-w-0 space-y-2', className)}>
            <Label htmlFor={id}>
                {label}
                {optional && (
                    <>
                        {' '}
                        <span className="font-normal text-muted-foreground">
                            (optional)
                        </span>
                    </>
                )}
            </Label>
            {children({
                id,
                'aria-invalid': error ? true : undefined,
                'aria-required': required ? true : undefined,
                'aria-describedby': describedBy,
            })}
            {hint && (
                <p id={`${id}-hint`} className="text-xs text-muted-foreground">
                    {hint}
                </p>
            )}
            {error && (
                <p
                    id={`${id}-error`}
                    className="text-sm text-destructive-foreground"
                >
                    {error}
                </p>
            )}
        </div>
    );
}

type AffixInputProps = ComponentProps<typeof Input> & {
    id: string;
    /** Shown inside the input, before the value (a currency symbol). */
    startAffix?: string;
    /** Shown inside the input, after the value (a unit). */
    endAffix?: string;
};

/**
 * Text input with a visible prefix and/or suffix. The affixes are announced
 * with the field (they are part of `aria-describedby`), so "pc" or "€" is not
 * lost on screen readers. Padding follows the affix length.
 */
export function AffixInput({
    id,
    startAffix,
    endAffix,
    style,
    'aria-describedby': describedBy,
    ...props
}: AffixInputProps) {
    const startId = startAffix ? `${id}-start` : undefined;
    const endId = endAffix ? `${id}-end` : undefined;
    const ids = [startId, endId, describedBy].filter(Boolean).join(' ');

    return (
        <div className="relative">
            <Input
                {...props}
                id={id}
                aria-describedby={ids || undefined}
                style={{
                    ...(startAffix && {
                        paddingLeft: `calc(1.5rem + ${startAffix.length}ch)`,
                    }),
                    ...(endAffix && {
                        paddingRight: `calc(1.5rem + ${endAffix.length}ch)`,
                    }),
                    ...style,
                }}
            />
            {startAffix && (
                <span
                    id={startId}
                    className="pointer-events-none absolute inset-y-0 left-3 flex items-center text-sm text-muted-foreground"
                >
                    {startAffix}
                </span>
            )}
            {endAffix && (
                <span
                    id={endId}
                    className="pointer-events-none absolute inset-y-0 right-3 flex items-center text-sm font-medium text-muted-foreground"
                >
                    {endAffix}
                </span>
            )}
        </div>
    );
}

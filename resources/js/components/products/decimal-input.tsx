import { useId } from 'react';
import type { ComponentProps } from 'react';
import { Input } from '@/components/ui/input';
import { EMPTY_VALUE } from '@/lib/format';
import { cn } from '@/lib/utils';

type DecimalInputProps = Omit<ComponentProps<typeof Input>, 'type'> & {
    /** Shown before the field, outside it (a currency symbol or code). */
    addonStart?: string;
    /** Shown inside the field, on the right (a unit or "%"). An em dash means "not known yet". */
    addonEnd?: string;
};

/**
 * A text input for a decimal value (never `type="number"`, which coerces and
 * breaks locales) with an optional currency before it and unit after it. The
 * addons are announced with the field through `aria-describedby`.
 */
export function DecimalInput({
    addonStart,
    addonEnd,
    className,
    'aria-describedby': describedBy,
    ...props
}: DecimalInputProps) {
    const uid = useId();
    const startId = `${uid}-start`;
    const endId = `${uid}-end`;
    const describedByEnd = addonEnd !== undefined && addonEnd !== EMPTY_VALUE;
    // The addon (unit, currency) is read first, then the error message.
    const describedby =
        [
            addonStart ? startId : null,
            describedByEnd ? endId : null,
            describedBy,
        ]
            .filter(Boolean)
            .join(' ') || undefined;

    return (
        <div className="flex">
            {addonStart && (
                <span
                    id={startId}
                    className="inline-flex items-center rounded-l-md border border-r-0 border-input bg-muted px-3 text-sm text-muted-foreground"
                >
                    {addonStart}
                </span>
            )}
            <div className="relative min-w-0 flex-1">
                <Input
                    {...props}
                    type="text"
                    inputMode="decimal"
                    autoComplete="off"
                    aria-describedby={describedby}
                    className={cn(
                        addonStart && 'rounded-l-none',
                        addonEnd && 'pr-14',
                        className,
                    )}
                />
                {addonEnd && (
                    <span
                        id={endId}
                        className="pointer-events-none absolute inset-y-0 right-3 flex items-center text-sm text-muted-foreground"
                    >
                        {addonEnd}
                    </span>
                )}
            </div>
        </div>
    );
}

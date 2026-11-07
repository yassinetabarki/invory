import { Check, Lock } from 'lucide-react';
import { ToggleGroup, ToggleGroupItem } from '@/components/ui/toggle-group';
import {
    Tooltip,
    TooltipContent,
    TooltipProvider,
    TooltipTrigger,
} from '@/components/ui/tooltip';
import { cn } from '@/lib/utils';
import type { SizeOption } from '@/types/domain';

type SizesFieldProps = {
    /** Base of the ids that tie the label, hint, count and errors to the group. */
    id: string;
    /** Every size of the workshop, in list order. */
    sizes: SizeOption[];
    /** Ids of the chosen sizes. */
    value: number[];
    /** Called with the new ids, in list order, when she presses a size. */
    onChange: (ids: number[]) => void;
    hint: string;
    /** The field's name; "Sizes" unless a page words it otherwise ("Suggested sizes"). */
    label?: string;
    /** Size id → why it can't be taken off (Edit product). */
    locks?: Record<number, string>;
    /** Why One size can't be chosen, or null. */
    oneSizeLock?: string | null;
    /** Why no other size can be chosen, or null. */
    otherSizesLock?: string | null;
    /** Server sentences, one line each; the group's first button takes the focus. */
    error?: string[];
    /** Add product: the sizes were kept on a category change; the button replaces them. */
    kept?: { categoryName: string; onUse: () => void } | null;
    /** The wrapper's `id`, the target of `#sizes` links; give another one where a page holds two fields. */
    anchorId?: string;
    className?: string;
};

function countLine(count: number): string {
    if (count === 0) {
        return 'None chosen yet';
    }

    return `${count} ${count === 1 ? 'size' : 'sizes'} chosen`;
}

/**
 * "Sizes" of a product: one toggle button per size of the workshop's list, all
 * visible. A pressed size has a tint, a 2 px ring and a check mark (never colour
 * alone). One size and the other sizes exclude each other. A locked size stays
 * pressed and, like a size that can't be chosen, is `aria-disabled` (not
 * `disabled`): the keyboard still reaches it and its reason is its description
 * and its tip. The group is one tab stop (arrow keys move, Space presses).
 */
export function SizesField({
    id,
    sizes,
    value,
    onChange,
    hint,
    label = 'Sizes',
    locks = {},
    oneSizeLock = null,
    otherSizesLock = null,
    error = [],
    kept = null,
    anchorId = 'sizes',
    className,
}: SizesFieldProps) {
    const labelId = `${id}-label`;
    const hintId = `${id}-hint`;
    const lockNoteId = `${id}-lock-note`;
    const errorIds = error.map((_, index) => `${id}-error-${index}`);
    const hasLocks = Object.keys(locks).length > 0;
    const describedBy = [hintId, hasLocks ? lockNoteId : null, ...errorIds]
        .filter(Boolean)
        .join(' ');

    /** Why this size can't be pressed now, or null. */
    function reasonFor(size: SizeOption): string | null {
        return (
            locks[size.id] ??
            (size.one_size ? oneSizeLock : otherSizesLock) ??
            null
        );
    }

    function handleValueChange(next: string[]) {
        const before = new Set(value.map(String));
        const changed = sizes.find(
            (size) =>
                before.has(String(size.id)) !== next.includes(String(size.id)),
        );

        if (!changed || reasonFor(changed) !== null) {
            return;
        }

        let ids: Set<number>;

        if (!before.has(String(changed.id))) {
            // One size turns the others off; another size turns One size off.
            const keep = value.filter(
                (chosen) =>
                    !changed.one_size &&
                    !sizes.find((size) => size.id === chosen)?.one_size,
            );

            ids = new Set([...keep, changed.id]);
        } else {
            ids = new Set(value.filter((chosen) => chosen !== changed.id));
        }

        onChange(sizes.filter((size) => ids.has(size.id)).map((s) => s.id));
    }

    return (
        <div
            id={anchorId}
            className={cn('flex min-w-0 scroll-mt-4 flex-col gap-2', className)}
        >
            <span id={labelId} className="text-sm leading-none font-medium">
                {label}
                <span
                    aria-hidden="true"
                    className="text-destructive-foreground"
                >
                    {' '}
                    *
                </span>
            </span>

            <p id={hintId} className="text-xs text-muted-foreground">
                {hint}
            </p>

            <TooltipProvider>
                <ToggleGroup
                    type="multiple"
                    role="group"
                    value={value.map(String)}
                    onValueChange={handleValueChange}
                    aria-labelledby={labelId}
                    aria-describedby={describedBy}
                    className="flex-wrap justify-start gap-2"
                >
                    {sizes.map((size, index) => {
                        const pressed = value.includes(size.id);
                        const reason = reasonFor(size);
                        const reasonId = `${id}-reason-${size.id}`;
                        // The first button, where the focus lands after a failed save, also
                        // points at the error sentences (as the Colours field's button does),
                        // so they are announced with it.
                        const invalid = index === 0 && error.length > 0;
                        const buttonDescription = [
                            reason !== null ? reasonId : null,
                            ...(invalid ? errorIds : []),
                        ]
                            .filter(Boolean)
                            .join(' ');
                        const button = (
                            <ToggleGroupItem
                                value={String(size.id)}
                                aria-disabled={reason !== null || undefined}
                                aria-describedby={
                                    buttonDescription || undefined
                                }
                                data-invalid={invalid ? 'true' : undefined}
                                className={cn(
                                    'h-11 min-w-11 rounded-md border border-input px-3 md:h-10',
                                    'data-[state=on]:border-primary data-[state=on]:bg-primary/10 data-[state=on]:text-foreground data-[state=on]:inset-ring-1 data-[state=on]:inset-ring-primary',
                                    'data-[invalid=true]:border-destructive',
                                    reason !== null && 'cursor-not-allowed',
                                    reason !== null && !pressed && 'opacity-60',
                                )}
                            >
                                {/* The check's room is always kept, so a chip keeps its width when pressed. */}
                                <Check
                                    aria-hidden="true"
                                    className={cn(
                                        'size-4',
                                        !pressed && 'invisible',
                                    )}
                                />
                                {size.label}
                                {locks[size.id] !== undefined && (
                                    <Lock
                                        aria-hidden="true"
                                        className="size-3.5"
                                    />
                                )}
                            </ToggleGroupItem>
                        );

                        if (reason === null) {
                            return (
                                <span key={size.id} className="inline-flex">
                                    {button}
                                </span>
                            );
                        }

                        // The tip sits on a wrapper: a tooltip trigger on the button itself
                        // would overwrite the button's own on/off state attribute.
                        return (
                            <Tooltip key={size.id}>
                                <TooltipTrigger asChild>
                                    <span className="inline-flex">
                                        {button}
                                    </span>
                                </TooltipTrigger>
                                <TooltipContent>{reason}</TooltipContent>
                                <span id={reasonId} className="sr-only">
                                    {reason}
                                </span>
                            </Tooltip>
                        );
                    })}
                </ToggleGroup>
            </TooltipProvider>

            <p aria-live="polite" className="text-sm text-muted-foreground">
                {countLine(value.length)}
            </p>

            {hasLocks && (
                <p id={lockNoteId} className="text-xs text-muted-foreground">
                    Sizes with a lock are in use by this product&apos;s stock,
                    orders, barcodes, production or material quantities, so they
                    can&apos;t be taken off.
                </p>
            )}

            {kept && (
                <p className="text-sm text-muted-foreground">
                    Sizes kept: you changed them.{' '}
                    <button
                        type="button"
                        className="inline-flex min-h-11 items-center rounded-sm font-medium text-primary underline-offset-2 outline-none hover:underline focus-visible:ring-[3px] focus-visible:ring-ring md:min-h-0"
                        onClick={kept.onUse}
                    >
                        Use the sizes of {kept.categoryName}
                    </button>
                </p>
            )}

            {error.map((sentence, index) => (
                <p
                    key={errorIds[index]}
                    id={errorIds[index]}
                    className="text-sm text-destructive-foreground"
                >
                    {sentence}
                </p>
            ))}
        </div>
    );
}

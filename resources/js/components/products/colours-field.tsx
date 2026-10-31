import { Plus, X } from 'lucide-react';
import { useRef, useState } from 'react';
import { ColourPicker } from '@/components/products/colour-picker';
import { ColourTag } from '@/components/shared/colour-tag';
import { Button } from '@/components/ui/button';
import { cn } from '@/lib/utils';
import type { Colour } from '@/types/domain';

type ColoursFieldProps = {
    /** Base of the ids that tie the hint and the error to the button. */
    id: string;
    /** The workshop's whole list, in list order. */
    colours: Colour[];
    /** Ids of the product's colours. */
    value: number[];
    onChange: (ids: number[]) => void;
    /** Server validation error ("Choose at least one colour."). */
    error?: string;
    className?: string;
};

/**
 * "Colours" of a product: the chosen colours as chips (swatch, name and a ✕
 * that only takes the colour off the form), then **Add colour**, which opens
 * the picker. Chips and the submitted ids always follow the list's order,
 * whatever order they were picked in.
 */
export function ColoursField({
    id,
    colours,
    value,
    onChange,
    error,
    className,
}: ColoursFieldProps) {
    const [open, setOpen] = useState(false);
    const addRef = useRef<HTMLButtonElement>(null);
    const hintId = `${id}-hint`;
    const errorId = `${id}-error`;

    const chosen = colours.filter((colour) => value.includes(colour.id));

    function setChosen(ids: Set<number>) {
        onChange(
            colours.filter((colour) => ids.has(colour.id)).map((c) => c.id),
        );
    }

    function toggle(colourId: number) {
        const ids = new Set(value);

        if (!ids.delete(colourId)) {
            ids.add(colourId);
        }

        setChosen(ids);
    }

    function remove(colourId: number) {
        const ids = new Set(value);

        ids.delete(colourId);
        setChosen(ids);
        // The chip with the focus is gone: keep the focus in the field.
        addRef.current?.focus();
    }

    return (
        <div className={cn('flex min-w-0 flex-col gap-2', className)}>
            <span className="text-sm leading-none font-medium">
                Colours
                <span
                    aria-hidden="true"
                    className="text-destructive-foreground"
                >
                    {' '}
                    *
                </span>
            </span>

            <p id={hintId} className="text-xs text-muted-foreground">
                The colours this product is made in, from your list in Settings
                › Colours.
            </p>

            <div
                role="group"
                aria-label="Colours"
                className="flex flex-wrap items-center gap-2"
            >
                {chosen.map((colour) => (
                    <span
                        key={colour.id}
                        className="inline-flex min-h-11 max-w-full min-w-0 items-center gap-1 rounded-full border bg-card pl-3 text-sm md:min-h-8 md:pl-2.5"
                    >
                        <ColourTag colour={colour} className="py-1" />
                        <button
                            type="button"
                            aria-label={`Remove ${colour.name}`}
                            className="inline-flex size-11 shrink-0 items-center justify-center rounded-full text-muted-foreground outline-none hover:bg-accent hover:text-accent-foreground focus-visible:ring-[3px] focus-visible:ring-ring md:size-8"
                            onClick={() => remove(colour.id)}
                        >
                            <X aria-hidden="true" className="size-4" />
                        </button>
                    </span>
                ))}

                <ColourPicker
                    colours={colours}
                    chosen={value}
                    onToggle={toggle}
                    open={open}
                    onOpenChange={setOpen}
                    trigger={
                        <Button
                            ref={addRef}
                            type="button"
                            variant="outline"
                            // Where the focus goes when there is no colour (read by the form).
                            data-invalid={error ? 'true' : undefined}
                            aria-describedby={[hintId, error ? errorId : null]
                                .filter(Boolean)
                                .join(' ')}
                            className="h-11 w-full data-[invalid=true]:border-destructive md:h-9 md:w-auto"
                        >
                            <Plus aria-hidden="true" />
                            Add colour
                        </Button>
                    }
                />
            </div>

            {error && (
                <p id={errorId} className="text-sm text-destructive-foreground">
                    {error}
                </p>
            )}
        </div>
    );
}

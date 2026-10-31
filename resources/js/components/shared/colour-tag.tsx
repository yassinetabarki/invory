import { cn } from '@/lib/utils';
import type { Colour } from '@/types/domain';

type ColourSwatchProps = {
    /** `#RRGGBB`, validated by the server. */
    swatch: string;
    className?: string;
};

/**
 * The colour itself: a small rounded square, the same fill in light and dark
 * mode (it is the fabric's colour), edged with the grey of a field's border so
 * White shows on a white card and Black in dark mode. Always shown next to the
 * colour's name, and skipped by screen readers.
 */
export function ColourSwatch({ swatch, className }: ColourSwatchProps) {
    return (
        <span
            aria-hidden="true"
            data-slot="colour-swatch"
            style={{ backgroundColor: swatch }}
            className={cn(
                'inline-block size-4 shrink-0 rounded-[4px] border border-input',
                className,
            )}
        />
    );
}

type ColourTagProps = {
    colour: Pick<Colour, 'name' | 'swatch'>;
    className?: string;
};

/** A colour as people read it: its swatch and its name, no border. */
export function ColourTag({ colour, className }: ColourTagProps) {
    return (
        <span
            className={cn(
                'inline-flex max-w-full min-w-0 items-center gap-2',
                className,
            )}
        >
            <ColourSwatch swatch={colour.swatch} />
            {/* A 32-character name without a space breaks anywhere, so it never widens its box. */}
            <span className="min-w-0 [overflow-wrap:anywhere]">
                {colour.name}
            </span>
        </span>
    );
}

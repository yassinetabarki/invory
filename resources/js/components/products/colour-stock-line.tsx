import { useId, useState } from 'react';
import { ColourSwatch } from '@/components/shared/colour-tag';
import { Quantity } from '@/components/shared/quantity';
import { Button } from '@/components/ui/button';
import { useAppSettings } from '@/hooks/use-app-settings';
import { formatCount, RANGE_SEPARATOR } from '@/lib/format';
import { cn } from '@/lib/utils';
import type {
    ColourStockReading,
    CountRange,
    ProductComponentLine,
} from '@/types/domain';

/** Asks the page to add a colour the material lacks (INV-21-p opens its window); the link shows only when this is given. */
export type AddColour = (materialId: number, colourId: number) => void;

/** Up to this many colours are read in a line; more go behind a "5 colours" button. */
const MAX_INLINE_COLOURS = 3;

/** "Add Light wash to Denim 12oz": the link beside every "no stock" colour. */
export function AddColourLink({
    materialName,
    colourName,
    onClick,
    className,
}: {
    materialName: string;
    colourName: string;
    onClick: () => void;
    className?: string;
}) {
    return (
        <Button
            type="button"
            variant="link"
            onClick={onClick}
            className={cn(
                'h-auto min-h-11 p-0 text-left text-sm whitespace-normal md:min-h-0',
                className,
            )}
        >
            Add {colourName} to {materialName}
        </Button>
    );
}

/** The "Light wash — no stock" line of a colour the material lacks, in the warning tone. */
function NoStock() {
    return (
        <span className="font-semibold text-warning-foreground">
            — no stock
        </span>
    );
}

/** One colour with its stock ("Indigo 250 m"), or "Light wash — no stock". */
function Reading({
    reading,
    unit,
}: {
    reading: ColourStockReading;
    unit: ProductComponentLine['material']['unit'];
}) {
    return (
        <span>
            <ColourSwatch
                swatch={reading.colour.swatch}
                className="mr-1.5 align-middle"
            />
            {reading.colour.name}{' '}
            {reading.missing ? (
                <NoStock />
            ) : (
                <Quantity value={reading.stock_on_hand} unit={unit} />
            )}
        </span>
    );
}

/**
 * The composition's Stock on hand of a material kept per colour: the product's
 * own colours with their stock, one colour per line ("Indigo 250 m", "Black
 * 40 m"; four or more behind a "5 colours" button), and, for each colour the material lacks, the link to add
 * it when the page can. Every figure is the server's.
 */
export function ColourStockLine({
    line,
    onAddColour,
    alignEnd = false,
}: {
    line: ProductComponentLine;
    onAddColour?: AddColour;
    alignEnd?: boolean;
}) {
    const [open, setOpen] = useState(false);
    const listId = useId();
    const readings = line.stock_by_colour ?? [];
    const { material } = line;

    return (
        <span
            className={cn(
                'flex flex-col gap-1',
                alignEnd ? 'items-end text-right' : 'items-start',
            )}
        >
            {readings.length > MAX_INLINE_COLOURS ? (
                <>
                    <Button
                        type="button"
                        variant="link"
                        aria-expanded={open}
                        aria-controls={listId}
                        onClick={() => setOpen((value) => !value)}
                        className="h-auto min-h-11 p-0 text-sm md:min-h-0"
                    >
                        {readings.length} colours
                    </Button>
                    {open && (
                        <ul id={listId} className="space-y-1">
                            {readings.map((reading) => (
                                <li key={reading.colour.id}>
                                    <Reading
                                        reading={reading}
                                        unit={material.unit}
                                    />
                                </li>
                            ))}
                        </ul>
                    )}
                </>
            ) : (
                <ul className="space-y-1">
                    {readings.map((reading) => (
                        <li key={reading.colour.id}>
                            <Reading reading={reading} unit={material.unit} />
                        </li>
                    ))}
                </ul>
            )}
            {onAddColour &&
                readings
                    .filter((reading) => reading.missing)
                    .map((reading) => (
                        <AddColourLink
                            key={reading.colour.id}
                            materialName={material.name}
                            colourName={reading.colour.name}
                            onClick={() =>
                                onAddColour(material.id, reading.colour.id)
                            }
                        />
                    ))}
        </span>
    );
}

/** "103–142 units", or "126 units" when the colour's sizes agree. The counts are the server's. */
function unitsText(range: CountRange, locale: string): string {
    const count =
        range.min === range.max
            ? formatCount(range.max, locale)
            : `${formatCount(range.min, locale)}${RANGE_SEPARATOR}${formatCount(range.max, locale)}`;

    return `${count} ${range.max === 1 ? 'unit' : 'units'}`;
}

/**
 * **View by colour** under "Varies": what the material's stock makes of the
 * product in each colour, one per line ("Indigo 103–142 units", "Black 16–22
 * units").
 */
export function UnitsByColour({
    line,
    alignEnd = false,
}: {
    line: ProductComponentLine;
    alignEnd?: boolean;
}) {
    const { locale } = useAppSettings();
    const [open, setOpen] = useState(false);
    const panelId = useId();
    const readings = line.stock_by_colour ?? [];

    return (
        <span
            className={cn(
                'flex flex-col gap-1',
                alignEnd ? 'items-end text-right' : 'items-start',
            )}
        >
            <Button
                type="button"
                variant="link"
                aria-expanded={open}
                aria-controls={panelId}
                aria-label={`View by colour for ${line.material.name}`}
                onClick={() => setOpen((value) => !value)}
                className="h-auto min-h-11 p-0 text-sm md:min-h-0"
            >
                View by colour
            </Button>
            {open && (
                <ul id={panelId} className="space-y-1 text-sm">
                    {readings.map((reading) => (
                        <li key={reading.colour.id}>
                            <ColourSwatch
                                swatch={reading.colour.swatch}
                                className="mr-1.5 align-middle"
                            />
                            {reading.colour.name}{' '}
                            {reading.missing
                                ? 'no stock'
                                : unitsText(reading.units_possible, locale)}
                        </li>
                    ))}
                </ul>
            )}
        </span>
    );
}

import { SlidersHorizontal } from 'lucide-react';
import { useState } from 'react';
import { RestockProductPicker } from '@/components/restocks/restock-product-picker';
import { ColourTag } from '@/components/shared/colour-tag';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import {
    Sheet,
    SheetContent,
    SheetDescription,
    SheetHeader,
    SheetTitle,
} from '@/components/ui/sheet';
import { useIsMobile } from '@/hooks/use-mobile';
import type { Colour, RestockProductRef } from '@/types/domain';

const ALL = 'all';

type ProgressFiltersProps = {
    product: RestockProductRef | null;
    colour: number | null;
    colours: Colour[];
    onProduct: (product: RestockProductRef | null) => void;
    onColour: (colour: number | null) => void;
};

function Fields({
    product,
    colour,
    colours,
    onProduct,
    onColour,
    suffix,
}: ProgressFiltersProps & { suffix: string }) {
    return (
        <>
            <div className="flex flex-col gap-1.5 sm:w-60">
                <span
                    id={`progress-product-label${suffix}`}
                    className="text-sm font-medium"
                >
                    Product
                </span>
                <RestockProductPicker
                    mode="filter"
                    id={`progress-product${suffix}`}
                    labelledBy={`progress-product-label${suffix}`}
                    value={product}
                    onSelect={onProduct}
                />
            </div>
            <div className="flex flex-col gap-1.5 sm:w-56">
                <Label htmlFor={`progress-colour${suffix}`}>Colour</Label>
                <Select
                    value={colour === null ? ALL : String(colour)}
                    onValueChange={(value) =>
                        onColour(value === ALL ? null : Number(value))
                    }
                >
                    <SelectTrigger
                        id={`progress-colour${suffix}`}
                        className="h-11 w-full sm:h-9"
                    >
                        <SelectValue />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem value={ALL}>All colours</SelectItem>
                        {colours.map((item) => (
                            <SelectItem key={item.id} value={String(item.id)}>
                                <ColourTag colour={item} />
                            </SelectItem>
                        ))}
                    </SelectContent>
                </Select>
            </div>
        </>
    );
}

/**
 * Product and Colour (INV-5, AC-3). On a phone they sit behind **Filters**
 * ("Filters · 1" when one is set), in a panel.
 */
export function ProgressFiltersBar(props: ProgressFiltersProps) {
    const isMobile = useIsMobile();
    const [open, setOpen] = useState(false);
    const set = (props.product ? 1 : 0) + (props.colour === null ? 0 : 1);

    if (!isMobile) {
        return (
            <div
                role="search"
                aria-label="Filter progress"
                className="flex gap-3"
            >
                <Fields {...props} suffix="" />
            </div>
        );
    }

    return (
        <>
            <Button
                type="button"
                variant="outline"
                className="h-11 w-full"
                onClick={() => setOpen(true)}
            >
                <SlidersHorizontal aria-hidden="true" />
                {set === 0 ? 'Filters' : `Filters · ${set}`}
            </Button>
            <Sheet open={open} onOpenChange={setOpen}>
                <SheetContent side="bottom" className="h-full">
                    <SheetHeader>
                        <SheetTitle>Filters</SheetTitle>
                        <SheetDescription className="sr-only">
                            Choose a product or a colour.
                        </SheetDescription>
                    </SheetHeader>
                    <div className="flex flex-col gap-4 px-4 pb-6">
                        <Fields
                            {...props}
                            suffix="-sheet"
                            onProduct={(next) => {
                                props.onProduct(next);
                                setOpen(false);
                            }}
                            onColour={(next) => {
                                props.onColour(next);
                                setOpen(false);
                            }}
                        />
                    </div>
                </SheetContent>
            </Sheet>
        </>
    );
}

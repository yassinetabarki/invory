import { useHttp } from '@inertiajs/react';
import { Barcode, Check, CircleAlert } from 'lucide-react';
import { useEffect, useRef, useState } from 'react';
import RestockBarcodeController from '@/actions/App/Http/Controllers/RestockBarcodeController';
import { variantName } from '@/components/restocks/restock-line';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import type { RestockBarcodeLookup, RestockLine } from '@/types/domain';

/** What one scan did, as the line under the field says it. */
type Outcome =
    | { kind: 'added'; text: string }
    | { kind: 'error'; text: string }
    | {
          kind: 'choose';
          text: string;
          number: string;
          colourId: number;
          sizeId: number;
          matches: RestockLine[];
      };

type ScanFieldProps = {
    /** The products already on the restock: a shared barcode goes to the one that is there, without asking. */
    chosen: number[];
    /**
     * Adds one unit of this colour and size (the product's line is added at the end first when it isn't there).
     * Returns the units now typed there, or an error to show (the 50-product limit).
     */
    onAdd: (
        line: RestockLine,
        colourId: number,
        sizeId: number,
    ) => { units: number } | { error: string };
};

/** "Added 1 · Denim Jacket · Indigo · M · 8 on this restock"; a "One size" product: "Added 1 · Leather Tote · Camel · 1 on this restock". */
function addedText(
    line: RestockLine,
    colourId: number,
    sizeId: number,
    units: number,
): string {
    const colour =
        line.stock.colours.find((entry) => entry.colour.id === colourId)?.colour
            .name ?? '';
    const size = line.sizes.find((entry) => entry.id === sizeId)?.label ?? '';
    const parts = [
        'Added 1',
        line.product.name,
        colour,
        ...(line.one_size ? [] : [size]),
        `${units} on this restock`,
    ];

    return parts.join(' · ');
}

/**
 * "Scan or type a barcode" on New restock (INV-3, AC-4). A barcode scanner
 * types the 13 digits and Enter; typing them and pressing **Add 1** does the
 * same. Each scan adds one unit to its colour and size, the focus stays in the
 * field for the next label, and the line under it says what happened: a green
 * "Added 1 · …", the server's red message, or, for a barcode shared by
 * several products, a choice between their SKUs.
 */
export function ScanField({ chosen, onAdd }: ScanFieldProps) {
    const { get } = useHttp<Record<string, never>, RestockBarcodeLookup>();
    const [number, setNumber] = useState('');
    const [outcome, setOutcome] = useState<Outcome | null>(null);
    const inputRef = useRef<HTMLInputElement>(null);
    // The answer to a scan comes later: by then the restock may have changed, so the latest props are read through a ref.
    const latest = useRef({ chosen, onAdd });

    useEffect(() => {
        latest.current = { chosen, onAdd };
    });

    function add(line: RestockLine, colourId: number, sizeId: number) {
        const result = latest.current.onAdd(line, colourId, sizeId);

        setOutcome(
            'error' in result
                ? { kind: 'error', text: result.error }
                : {
                      kind: 'added',
                      text: addedText(line, colourId, sizeId, result.units),
                  },
        );
        inputRef.current?.focus();
    }

    function lookUp() {
        const typed = number.trim();

        if (typed === '') {
            inputRef.current?.focus();

            return;
        }

        setNumber('');
        inputRef.current?.focus();

        get(
            RestockBarcodeController.show.url({ query: { number: typed } }),
        ).then(
            (found) => {
                if (found.result !== 'found') {
                    setOutcome({ kind: 'error', text: found.message });

                    return;
                }

                const there = found.matches.find((line) =>
                    latest.current.chosen.includes(line.product.id),
                );

                if (found.matches.length === 1 || there) {
                    add(
                        there ?? found.matches[0],
                        found.colour_id,
                        found.size_id,
                    );

                    return;
                }

                const first = found.matches[0];
                const colour =
                    first.stock.colours.find(
                        (entry) => entry.colour.id === found.colour_id,
                    )?.colour.name ?? '';
                const size = first.sizes.find(
                    (entry) => entry.id === found.size_id,
                ) ?? { id: found.size_id, label: '' };

                setOutcome({
                    kind: 'choose',
                    text: `${typed} is ${variantName(colour, size, first.one_size)} on ${found.matches.length} products named ${first.product.name}. Which one?`,
                    number: typed,
                    colourId: found.colour_id,
                    sizeId: found.size_id,
                    matches: found.matches,
                });
            },
            () =>
                setOutcome({
                    kind: 'error',
                    text: "Couldn't look up the barcode. Try again.",
                }),
        );
    }

    return (
        <section
            aria-labelledby="restock-scan"
            className="flex flex-col gap-3 rounded-xl border bg-card p-4 text-card-foreground"
        >
            <h2 id="restock-scan" className="font-semibold">
                Scan or type a barcode
            </h2>
            <div className="flex flex-col gap-2 sm:flex-row">
                <div className="relative flex-1">
                    <Label htmlFor="restock-scan-field" className="sr-only">
                        Scan or type a barcode
                    </Label>
                    <Barcode
                        className="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground"
                        aria-hidden="true"
                    />
                    <Input
                        ref={inputRef}
                        id="restock-scan-field"
                        inputMode="numeric"
                        autoComplete="off"
                        className="h-11 pl-9 font-mono sm:h-9"
                        aria-describedby="restock-scan-hint restock-scan-result"
                        value={number}
                        onChange={(event) => setNumber(event.target.value)}
                        onKeyDown={(event) => {
                            if (event.key === 'Enter') {
                                // A scanner ends with Enter: it adds the unit, it never saves the restock.
                                event.preventDefault();
                                lookUp();
                            }
                        }}
                    />
                </div>
                <Button
                    type="button"
                    variant="outline"
                    className="max-sm:h-11 max-sm:w-full"
                    onClick={lookUp}
                >
                    Add 1
                </Button>
            </div>
            <p id="restock-scan-hint" className="text-sm text-muted-foreground">
                Each scan adds one unit to its colour and size. A barcode
                scanner works like typing.
            </p>
            <div
                id="restock-scan-result"
                aria-live="polite"
                className="empty:hidden"
            >
                {outcome?.kind === 'added' && (
                    <p className="flex items-center gap-2 text-sm text-success-foreground">
                        <Check className="size-4 shrink-0" aria-hidden="true" />
                        {outcome.text}
                    </p>
                )}
                {outcome?.kind === 'error' && (
                    <p className="flex items-center gap-2 text-sm text-destructive-foreground">
                        <CircleAlert
                            className="size-4 shrink-0"
                            aria-hidden="true"
                        />
                        {outcome.text}
                    </p>
                )}
                {outcome?.kind === 'choose' && (
                    <div className="flex flex-col gap-2 text-sm">
                        <p>{outcome.text}</p>
                        <div className="flex flex-wrap gap-2">
                            {outcome.matches.map((line) => (
                                <Button
                                    key={line.product.id}
                                    type="button"
                                    variant="outline"
                                    size="sm"
                                    className="font-mono max-sm:h-11"
                                    onClick={() =>
                                        add(
                                            line,
                                            outcome.colourId,
                                            outcome.sizeId,
                                        )
                                    }
                                >
                                    {line.product.sku}
                                </Button>
                            ))}
                        </div>
                    </div>
                )}
            </div>
        </section>
    );
}

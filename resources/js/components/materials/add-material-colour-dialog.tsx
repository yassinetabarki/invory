import { Form, useHttp } from '@inertiajs/react';
import { useEffect, useId, useRef, useState } from 'react';
import MaterialColourController from '@/actions/App/Http/Controllers/MaterialColourController';
import MaterialRestockSheetController from '@/actions/App/Http/Controllers/MaterialRestockSheetController';
import { currencySymbol } from '@/components/materials/currency-symbol';
import { AffixInput, FormField } from '@/components/materials/form-field';
import {
    AlertDialog,
    AlertDialogCancel,
    AlertDialogContent,
    AlertDialogDescription,
    AlertDialogFooter,
    AlertDialogHeader,
    AlertDialogTitle,
} from '@/components/ui/alert-dialog';
import { Button } from '@/components/ui/button';
import { Spinner } from '@/components/ui/spinner';
import { useAppSettings } from '@/hooks/use-app-settings';
import { formatQuantity } from '@/lib/format';
import type {
    Colour,
    Material,
    MaterialCostMode,
    MaterialRef,
} from '@/types/domain';

type AddMaterialColourDialogProps = {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    /** `cost_mode` is on the product page's line (INV-21); a caller without it lets the window ask the server. */
    material: Pick<MaterialRef, 'id' | 'name' | 'unit'> & {
        cost_mode?: MaterialCostMode;
    };
    colour: Pick<Colour, 'id' | 'name'>;
    /** The product the link was on: "Denim Jacket can't be made in Light wash" in the sentence. */
    productName: string;
    /** Where focus goes when the link that opened this window is gone by the time it closes (never <body>). */
    fallbackFocus?: () => HTMLElement | null;
};

/**
 * The window of "Add Light wash to Denim 12oz?" (INV-21-p): adds the colour to a
 * per-colour material at 0, with its cost when the material keeps a cost per
 * colour ("Unit cost of Light wash", required). It opens from every place that
 * shows the link (the product page, Start batch, "Used in"): mount it with the
 * material, the colour and the product's name and let the caller open it. The
 * server writes the toast "Light wash added to Denim 12oz." with its
 * "Restock Light wash" button; the window closes when the save succeeds. Focus
 * returns to the link that opened it, or to `fallbackFocus` when that link has
 * gone.
 *
 * The cost mode comes with the material when the caller has it (the product
 * page's composition line). Otherwise the window asks
 * `materials/{material}/restock-sheet` for it when it opens; until it lands the
 * field is hidden (a save without a cost is refused by the server, and the field
 * then shows with the server's message).
 */
export function AddMaterialColourDialog({
    open,
    onOpenChange,
    material,
    colour,
    productName,
    fallbackFocus,
}: AddMaterialColourDialogProps) {
    // Radix returns focus to a trigger only; this window has none, so keep the link that opened it.
    const openerRef = useRef<Element | null>(null);

    return (
        <AlertDialog open={open} onOpenChange={onOpenChange}>
            <AlertDialogContent
                onOpenAutoFocus={() => {
                    openerRef.current = document.activeElement;
                }}
                onCloseAutoFocus={(event) => {
                    event.preventDefault();

                    const opener = openerRef.current;

                    if (opener instanceof HTMLElement && opener.isConnected) {
                        opener.focus();
                    } else {
                        fallbackFocus?.()?.focus();
                    }
                }}
            >
                <AddColourBody
                    material={material}
                    colour={colour}
                    productName={productName}
                    onClose={() => onOpenChange(false)}
                />
            </AlertDialogContent>
        </AlertDialog>
    );
}

function AddColourBody({
    material,
    colour,
    productName,
    onClose,
}: Omit<AddMaterialColourDialogProps, 'open' | 'onOpenChange'> & {
    onClose: () => void;
}) {
    const uid = useId();
    const { currency, locale } = useAppSettings();
    const { get } = useHttp<Record<string, never>, { material: Material }>();
    const [fetchedCostMode, setFetchedCostMode] =
        useState<MaterialCostMode | null>(null);
    const knownCostMode = material.cost_mode ?? null;
    const costMode = knownCostMode ?? fetchedCostMode;

    // Mounted with the open window, so this asks once per opening, and only when the caller didn't bring the cost mode.
    useEffect(() => {
        if (knownCostMode !== null) {
            return;
        }

        let current = true;

        get(MaterialRestockSheetController.url(material.id)).then(
            (data) => {
                if (current && data?.material) {
                    setFetchedCostMode(data.material.cost_mode);
                }
            },
            () => undefined,
        );

        return () => {
            current = false;
        };
    }, [get, material.id, knownCostMode]);

    return (
        <>
            <AlertDialogHeader>
                <AlertDialogTitle>
                    Add {colour.name} to {material.name}?
                </AlertDialogTitle>
                <AlertDialogDescription>
                    {colour.name} starts at{' '}
                    {formatQuantity('0', { locale, unit: material.unit })}, so{' '}
                    {productName} can't be made in {colour.name} until you
                    restock it.
                </AlertDialogDescription>
            </AlertDialogHeader>
            <Form
                {...MaterialColourController.store.form(material.id)}
                options={{ preserveScroll: true, preserveState: true }}
                onSuccess={onClose}
                className="grid gap-4"
            >
                {({ errors, processing }) => (
                    <>
                        <input
                            type="hidden"
                            name="colour_id"
                            value={colour.id}
                        />
                        {errors.colour_id && (
                            <p
                                role="alert"
                                className="text-sm text-destructive-foreground"
                            >
                                {errors.colour_id}
                            </p>
                        )}
                        {(costMode === 'per_colour' || errors.unit_cost) && (
                            <FormField
                                id={`${uid}-unit-cost`}
                                label={`Unit cost of ${colour.name}`}
                                required
                                error={errors.unit_cost}
                            >
                                {(control) => (
                                    <AffixInput
                                        {...control}
                                        name="unit_cost"
                                        type="text"
                                        inputMode="decimal"
                                        autoComplete="off"
                                        autoFocus
                                        startAffix={currencySymbol(
                                            currency,
                                            locale,
                                        )}
                                        endAffix={`/ ${material.unit}`}
                                        className="h-11"
                                    />
                                )}
                            </FormField>
                        )}
                        <AlertDialogFooter>
                            <AlertDialogCancel className="h-11 md:h-9">
                                Cancel
                            </AlertDialogCancel>
                            <Button
                                type="submit"
                                className="h-11 md:h-9"
                                disabled={processing}
                            >
                                {processing && <Spinner />}
                                Add {colour.name}
                            </Button>
                        </AlertDialogFooter>
                    </>
                )}
            </Form>
        </>
    );
}

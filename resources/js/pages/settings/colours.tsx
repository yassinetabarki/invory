import { Head } from '@inertiajs/react';
import { Plus } from 'lucide-react';
import { useRef, useState } from 'react';
import Heading from '@/components/heading';
import { ColourFormDialog } from '@/components/settings/colour-form-dialog';
import { ColoursTable } from '@/components/settings/colours-table';
import { Button } from '@/components/ui/button';
import { index } from '@/routes/settings/colours';
import type { ColoursSettingsProps, ColourRow } from '@/types/domain';

/** `colour` stays set while the dialog closes, so its title doesn't flip to "Add" during the exit animation. */
type DialogTarget = { open: boolean; colour: ColourRow | null };

export default function Colours({ colours }: ColoursSettingsProps) {
    const [dialog, setDialog] = useState<DialogTarget>({
        open: false,
        colour: null,
    });
    const triggerRef = useRef<HTMLElement | null>(null);

    function open(colour: ColourRow | null, trigger: HTMLElement) {
        triggerRef.current = trigger;
        setDialog({ open: true, colour });
    }

    return (
        <>
            <Head title="Colours" />

            <h1 className="sr-only">Colours settings</h1>

            <div className="space-y-6">
                <div className="flex flex-col gap-3 xl:flex-row xl:items-start xl:justify-between">
                    <Heading
                        variant="small"
                        title="Colours"
                        description="The colours your products come in. Each one shows as its swatch and its name. Rename any of them, or add your own."
                    />
                    <div className="shrink-0">
                        <Button
                            type="button"
                            className="w-full xl:w-auto"
                            onClick={(event) => open(null, event.currentTarget)}
                        >
                            <Plus aria-hidden="true" />
                            Add colour
                        </Button>
                    </div>
                </div>

                <ColoursTable colours={colours} onEdit={open} />
            </div>

            <ColourFormDialog
                open={dialog.open}
                colour={dialog.colour}
                onOpenChange={(next) => {
                    if (!next) {
                        setDialog((current) => ({ ...current, open: false }));
                    }
                }}
                returnFocusTo={triggerRef}
            />
        </>
    );
}

Colours.layout = {
    breadcrumbs: [
        {
            title: 'Colours',
            href: index(),
        },
    ],
};

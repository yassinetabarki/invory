import { Head } from '@inertiajs/react';
import { Plus } from 'lucide-react';
import { useRef, useState } from 'react';
import Heading from '@/components/heading';
import { SizeFormDialog } from '@/components/settings/size-form-dialog';
import { SizesTable } from '@/components/settings/sizes-table';
import { Button } from '@/components/ui/button';
import { index } from '@/routes/settings/sizes';
import type { SizeRow, SizesSettingsProps } from '@/types/domain';

/** `size` stays set while the dialog closes, so its title doesn't flip to "Add" during the exit animation. */
type DialogTarget = { open: boolean; size: SizeRow | null };

const NOTE =
    'A size is never removed. Positions set the order sizes are listed in, on every screen and in every SKU. "One size" is for products that don\'t come in sizes: a product that has it has no other size.';

export default function Sizes({ sizes }: SizesSettingsProps) {
    const [dialog, setDialog] = useState<DialogTarget>({
        open: false,
        size: null,
    });
    const triggerRef = useRef<HTMLElement | null>(null);

    function open(size: SizeRow | null, trigger: HTMLElement) {
        triggerRef.current = trigger;
        setDialog({ open: true, size });
    }

    return (
        <>
            <Head title="Sizes" />

            <h1 className="sr-only">Sizes settings</h1>

            <div className="space-y-6">
                <div className="flex flex-col gap-3 md:flex-row md:items-start md:justify-between">
                    <Heading
                        variant="small"
                        title="Sizes"
                        description="The sizes your products come in. Each product picks the ones it has. Rename any of them, or add your own."
                    />
                    <div className="shrink-0">
                        <Button
                            type="button"
                            className="h-11 w-full md:h-9 md:w-auto"
                            onClick={(event) => open(null, event.currentTarget)}
                        >
                            <Plus aria-hidden="true" />
                            Add size
                        </Button>
                    </div>
                </div>

                <SizesTable sizes={sizes} onEdit={open} />

                <p className="text-sm text-muted-foreground">{NOTE}</p>
            </div>

            <SizeFormDialog
                open={dialog.open}
                size={dialog.size}
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

Sizes.layout = {
    breadcrumbs: [
        {
            title: 'Sizes',
            href: index(),
        },
    ],
};

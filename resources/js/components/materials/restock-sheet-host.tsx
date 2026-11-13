import { useHttp } from '@inertiajs/react';
import { useEffect, useState } from 'react';
import { toast } from 'sonner';
import MaterialRestockSheetController from '@/actions/App/Http/Controllers/MaterialRestockSheetController';
import { RestockSheet } from '@/components/materials/restock-sheet';
import { RESTOCK_SHEET_EVENT } from '@/hooks/use-flash-toast';
import type { Material } from '@/types/domain';
import type { RestockSheetRequest } from '@/types/ui';

type Opened = { material: Material; colourId: number };

/**
 * The one Restock sheet a toast can open (INV-21: "Restock Light wash" after a
 * colour is added). Mounted once in the app layout, which Inertia keeps across
 * visits, so it survives the reload that follows the save. It listens for
 * `RESTOCK_SHEET_EVENT` (dispatched by `useFlashToast`), fetches the material
 * from `materials/{material}/restock-sheet` (the page may not hold it) and opens
 * the sheet on that colour. If the data can't be loaded no sheet opens and a
 * toast reads "Couldn't open Restock for Denim 12oz. Try again." (INV-21 AC-7).
 */
export function RestockSheetHost() {
    const { get } = useHttp<Record<string, never>, { material: Material }>();
    const [opened, setOpened] = useState<Opened | null>(null);
    const [open, setOpen] = useState(false);

    useEffect(() => {
        function onRequest(event: Event) {
            const {
                material_id: materialId,
                colour_id: colourId,
                material_name: materialName,
            } = (event as CustomEvent<RestockSheetRequest>).detail;
            const failed = () =>
                toast.error(
                    `Couldn't open Restock for ${materialName}. Try again.`,
                );

            get(MaterialRestockSheetController.url(materialId)).then((data) => {
                if (data?.material) {
                    setOpened({ material: data.material, colourId });
                    setOpen(true);
                } else {
                    failed();
                }
            }, failed);
        }

        window.addEventListener(RESTOCK_SHEET_EVENT, onRequest);

        return () => window.removeEventListener(RESTOCK_SHEET_EVENT, onRequest);
    }, [get]);

    if (!opened) {
        return null;
    }

    return (
        <RestockSheet
            key={opened.material.id}
            material={opened.material}
            open={open}
            onOpenChange={setOpen}
            initialColourId={opened.colourId}
        />
    );
}

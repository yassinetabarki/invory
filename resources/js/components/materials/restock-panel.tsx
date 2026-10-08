import { InlinePanel } from '@/components/materials/inline-panel';
import {
    RestockForm,
    RestockSummary,
} from '@/components/materials/restock-form';
import type { Material } from '@/types/domain';

type RestockPanelProps = {
    id: string;
    material: Material;
    /** Closes the panel; the container returns focus to the opening button. */
    onClose: () => void;
};

/** The index's Restock panel: the quick form inside an inline panel. */
export function RestockPanel({ id, material, onClose }: RestockPanelProps) {
    return (
        <InlinePanel
            id={id}
            title={`Restock ${material.name}`}
            description={<RestockSummary material={material} />}
            closeLabel="Close restock"
            onClose={onClose}
        >
            <RestockForm
                material={material}
                autoFocus
                onCancel={onClose}
                onSuccess={onClose}
            />
        </InlinePanel>
    );
}

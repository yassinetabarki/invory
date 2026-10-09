import { Head } from '@inertiajs/react';
import { Ruler } from 'lucide-react';
import { ReferenceManager } from '@/components/settings/reference-manager';
import { UnitFormDialog } from '@/components/settings/unit-form-dialog';
import { describeFactor } from '@/lib/reference';
import {
    archive,
    destroy,
    index,
    restore,
    store,
    update,
} from '@/routes/settings/units';
import type { UnitRow, UnitsSettingsProps } from '@/types/domain';

const urls = {
    archive: (id: number) => archive.url(id),
    restore: (id: number) => restore.url(id),
    destroy: (id: number) => destroy.url(id),
};

const formUrls = {
    store: store.url(),
    update: (id: number) => update.url(id),
};

const columns = [
    { header: 'Dimension', cell: (unit: UnitRow) => unit.dimension_label },
    {
        header: 'Factor',
        cell: (unit: UnitRow) => (
            <span className="tabular-nums">{describeFactor(unit)}</span>
        ),
    },
];

export default function Units({ units, dimensions }: UnitsSettingsProps) {
    return (
        <>
            <Head title="Units" />

            <ReferenceManager
                title="Units"
                description="The units materials are measured in. Rename or archive any of them, or add your own."
                singular="unit"
                addLabel="Add unit"
                emptyDescription="Add the units your materials are measured in."
                emptyIcon={Ruler}
                rows={units}
                columns={columns}
                usedBy={{ singular: 'material', plural: 'materials' }}
                urls={urls}
                blockedReason={(unit) =>
                    unit.is_system
                        ? 'System units can be archived, not deleted'
                        : null
                }
                renderDialog={(dialog) => (
                    <UnitFormDialog
                        {...dialog}
                        dimensions={dimensions}
                        urls={formUrls}
                    />
                )}
            />
        </>
    );
}

Units.layout = {
    breadcrumbs: [
        {
            title: 'Units',
            href: index(),
        },
    ],
};

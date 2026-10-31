import { Pencil } from 'lucide-react';
import type { MouseEvent } from 'react';
import { ColourTag } from '@/components/shared/colour-tag';
import { Button } from '@/components/ui/button';
import {
    Table,
    TableBody,
    TableCaption,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { useAppSettings } from '@/hooks/use-app-settings';
import { formatCount } from '@/lib/format';
import type { ColourRow } from '@/types/domain';

type ColoursTableProps = {
    /** In list order. */
    colours: ColourRow[];
    onEdit: (colour: ColourRow, trigger: HTMLElement) => void;
};

/**
 * The workshop's colours. From `xl` up it is a table; below that, one card per
 * colour (the same breakpoint as the other catalogue lists). A colour is never
 * removed, so the only action is Edit.
 */
export function ColoursTable({ colours, onEdit }: ColoursTableProps) {
    const { locale } = useAppSettings();

    function usedBy({ usage_count }: ColourRow): string {
        if (usage_count === 0) {
            return 'Not used';
        }

        return `${formatCount(usage_count, locale)} ${
            usage_count === 1 ? 'product' : 'products'
        }`;
    }

    function editButton(colour: ColourRow, layout: 'table' | 'card') {
        const isTable = layout === 'table';

        return (
            <Button
                type="button"
                size={isTable ? 'icon' : 'sm'}
                variant={isTable ? 'ghost' : 'outline'}
                aria-label={`Edit ${colour.name}`}
                title="Edit"
                onClick={(event: MouseEvent<HTMLButtonElement>) =>
                    onEdit(colour, event.currentTarget)
                }
            >
                <Pencil aria-hidden="true" />
                {!isTable && 'Edit'}
            </Button>
        );
    }

    return (
        <>
            <div className="hidden rounded-lg border bg-card xl:block">
                <Table>
                    <TableCaption className="sr-only">Colours</TableCaption>
                    <TableHeader>
                        <TableRow>
                            <TableHead scope="col">Colour</TableHead>
                            <TableHead scope="col">Code</TableHead>
                            <TableHead scope="col">Used by</TableHead>
                            <TableHead scope="col" className="text-right">
                                <span className="sr-only">Actions</span>
                            </TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        {colours.map((colour) => (
                            <TableRow key={colour.id}>
                                <TableCell className="min-w-32 font-medium whitespace-normal">
                                    <ColourTag colour={colour} />
                                </TableCell>
                                <TableCell className="font-mono text-xs">
                                    {colour.code}
                                </TableCell>
                                <TableCell className="whitespace-nowrap tabular-nums">
                                    {usedBy(colour)}
                                </TableCell>
                                <TableCell className="text-right">
                                    {editButton(colour, 'table')}
                                </TableCell>
                            </TableRow>
                        ))}
                    </TableBody>
                </Table>
            </div>

            <ul aria-label="Colours" className="space-y-3 xl:hidden">
                {colours.map((colour) => (
                    <li
                        key={colour.id}
                        className="space-y-3 rounded-lg border bg-card p-4 text-card-foreground"
                    >
                        <p className="font-medium">
                            <ColourTag colour={colour} />
                        </p>
                        <dl className="grid grid-cols-2 gap-2 text-sm">
                            <div>
                                <dt className="text-xs text-muted-foreground">
                                    Code
                                </dt>
                                <dd className="font-mono text-xs">
                                    {colour.code}
                                </dd>
                            </div>
                            <div>
                                <dt className="text-xs text-muted-foreground">
                                    Used by
                                </dt>
                                <dd className="tabular-nums">
                                    {usedBy(colour)}
                                </dd>
                            </div>
                        </dl>
                        {editButton(colour, 'card')}
                    </li>
                ))}
            </ul>
        </>
    );
}

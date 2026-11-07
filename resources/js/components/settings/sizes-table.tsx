import { Pencil } from 'lucide-react';
import type { MouseEvent } from 'react';
import { Badge } from '@/components/ui/badge';
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
import type { SizeRow } from '@/types/domain';

type SizesTableProps = {
    /** In list order. */
    sizes: SizeRow[];
    onEdit: (size: SizeRow, trigger: HTMLElement) => void;
};

/** The size as a small outlined tag; the One size row carries a neutral "No size" badge after it, also after a rename. */
function SizeName({ size }: { size: SizeRow }) {
    return (
        <span className="inline-flex items-center gap-2">
            <Badge variant="outline" className="text-sm font-medium">
                {size.label}
            </Badge>
            {size.one_size && <Badge variant="secondary">No size</Badge>}
        </span>
    );
}

/**
 * The workshop's sizes. From `md` up it is a table; on a phone, one 56 px row per
 * size (there can be dozens, so not a card each). A size is never removed, so the
 * only action is Edit.
 */
export function SizesTable({ sizes, onEdit }: SizesTableProps) {
    const { locale } = useAppSettings();

    function usedBy({ usage_count }: SizeRow): string {
        if (usage_count === 0) {
            return 'Not used';
        }

        return `${formatCount(usage_count, locale)} ${
            usage_count === 1 ? 'product' : 'products'
        }`;
    }

    function editButton(size: SizeRow, className: string) {
        return (
            <Button
                type="button"
                size="sm"
                variant="outline"
                className={className}
                aria-label={`Edit ${size.label}`}
                onClick={(event: MouseEvent<HTMLButtonElement>) =>
                    onEdit(size, event.currentTarget)
                }
            >
                <Pencil aria-hidden="true" />
                Edit
            </Button>
        );
    }

    return (
        <>
            <div className="hidden rounded-lg border bg-card md:block">
                <Table>
                    <TableCaption className="sr-only">Sizes</TableCaption>
                    <TableHeader>
                        <TableRow>
                            <TableHead scope="col">Size</TableHead>
                            <TableHead scope="col">Position</TableHead>
                            <TableHead scope="col">Used by</TableHead>
                            <TableHead scope="col" className="text-right">
                                <span className="sr-only">Actions</span>
                            </TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        {sizes.map((size) => (
                            <TableRow key={size.id}>
                                <TableCell className="min-w-32 whitespace-normal">
                                    <SizeName size={size} />
                                </TableCell>
                                <TableCell className="tabular-nums">
                                    {size.position}
                                </TableCell>
                                <TableCell className="whitespace-nowrap tabular-nums">
                                    {usedBy(size)}
                                </TableCell>
                                <TableCell className="text-right">
                                    {editButton(size, '')}
                                </TableCell>
                            </TableRow>
                        ))}
                    </TableBody>
                </Table>
            </div>

            <ul
                aria-label="Sizes"
                className="divide-y rounded-lg border bg-card md:hidden"
            >
                {sizes.map((size) => (
                    <li
                        key={size.id}
                        className="flex min-h-14 items-center gap-3 px-3 py-1.5"
                    >
                        <div className="min-w-0 flex-1 space-y-0.5">
                            <p>
                                <SizeName size={size} />
                            </p>
                            <p className="text-sm text-muted-foreground tabular-nums">
                                {size.usage_count === 0
                                    ? usedBy(size)
                                    : `Used by ${usedBy(size)}`}
                            </p>
                        </div>
                        {editButton(size, 'h-11 shrink-0')}
                    </li>
                ))}
            </ul>
        </>
    );
}

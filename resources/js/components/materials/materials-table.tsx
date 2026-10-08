import { Link } from '@inertiajs/react';
import { Eye, Pencil, Trash2 } from 'lucide-react';
import { StockBadge } from '@/components/materials/stock-badge';
import { Money } from '@/components/shared/money';
import { Quantity } from '@/components/shared/quantity';
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
import type { Material } from '@/types/domain';

type MaterialsTableProps = {
    materials: Material[];
    /** URL builders (Wayfinder in the page, plain functions in tests). */
    hrefs: {
        show: (material: Material) => string;
        edit: (material: Material) => string;
    };
    /** The page opens the confirm dialog; this component never deletes. */
    onDelete: (material: Material) => void;
};

type RowActionsProps = Pick<MaterialsTableProps, 'hrefs' | 'onDelete'> & {
    material: Material;
};

/** Cost per one unit of the material, e.g. "€8.50 / m". */
function UnitCost({ material }: { material: Material }) {
    return (
        <>
            <Money value={material.unit_cost} precise />
            <span className="text-muted-foreground"> / {material.unit}</span>
        </>
    );
}

/** Icon-only actions for the table; every control names the material. */
function IconActions({ material, hrefs, onDelete }: RowActionsProps) {
    return (
        <div className="flex justify-end gap-1">
            <Button asChild variant="ghost" size="icon">
                <Link
                    href={hrefs.show(material)}
                    aria-label={`View ${material.name}`}
                    title="View"
                >
                    <Eye aria-hidden="true" />
                </Link>
            </Button>
            <Button asChild variant="ghost" size="icon">
                <Link
                    href={hrefs.edit(material)}
                    aria-label={`Edit ${material.name}`}
                    title="Edit"
                >
                    <Pencil aria-hidden="true" />
                </Link>
            </Button>
            <Button
                type="button"
                variant="ghost"
                size="icon"
                aria-label={`Delete ${material.name}`}
                title="Delete"
                className="text-destructive-foreground hover:text-destructive-foreground"
                onClick={() => onDelete(material)}
            >
                <Trash2 aria-hidden="true" />
            </Button>
        </div>
    );
}

/** Labelled actions for the mobile cards (larger touch targets). */
function TextActions({ material, hrefs, onDelete }: RowActionsProps) {
    return (
        <div className="flex flex-wrap gap-2">
            <Button asChild variant="outline" size="sm">
                <Link
                    href={hrefs.show(material)}
                    aria-label={`View ${material.name}`}
                >
                    <Eye aria-hidden="true" />
                    View
                </Link>
            </Button>
            <Button asChild variant="outline" size="sm">
                <Link
                    href={hrefs.edit(material)}
                    aria-label={`Edit ${material.name}`}
                >
                    <Pencil aria-hidden="true" />
                    Edit
                </Link>
            </Button>
            <Button
                type="button"
                variant="outline"
                size="sm"
                aria-label={`Delete ${material.name}`}
                className="text-destructive-foreground hover:text-destructive-foreground"
                onClick={() => onDelete(material)}
            >
                <Trash2 aria-hidden="true" />
                Delete
            </Button>
        </div>
    );
}

export function MaterialsTable({
    materials,
    hrefs,
    onDelete,
}: MaterialsTableProps) {
    return (
        <>
            {/* >= md: table */}
            <div className="hidden rounded-lg border md:block">
                <Table>
                    <TableCaption className="sr-only">Materials</TableCaption>
                    <TableHeader>
                        <TableRow>
                            <TableHead scope="col">SKU</TableHead>
                            <TableHead scope="col">Name</TableHead>
                            <TableHead scope="col">Category</TableHead>
                            <TableHead scope="col" className="text-right">
                                Stock
                            </TableHead>
                            <TableHead scope="col" className="text-right">
                                Unit cost
                            </TableHead>
                            <TableHead scope="col" className="text-right">
                                Stock value
                            </TableHead>
                            <TableHead scope="col">Status</TableHead>
                            <TableHead scope="col" className="text-right">
                                <span className="sr-only">Actions</span>
                            </TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        {materials.map((material) => (
                            <TableRow key={material.id}>
                                <TableCell className="font-mono text-xs">
                                    {material.sku}
                                </TableCell>
                                <TableCell className="max-w-64 font-medium whitespace-normal">
                                    <Link
                                        href={hrefs.show(material)}
                                        className="hover:underline focus-visible:underline"
                                    >
                                        {material.name}
                                    </Link>
                                </TableCell>
                                <TableCell>{material.category_label}</TableCell>
                                <TableCell className="text-right tabular-nums">
                                    <Quantity
                                        value={material.quantity_on_hand}
                                        unit={material.unit}
                                    />
                                </TableCell>
                                <TableCell className="text-right tabular-nums">
                                    <UnitCost material={material} />
                                </TableCell>
                                <TableCell className="text-right tabular-nums">
                                    <Money value={material.stock_value} />
                                </TableCell>
                                <TableCell>
                                    <StockBadge
                                        status={material.stock_status}
                                    />
                                </TableCell>
                                <TableCell>
                                    <IconActions
                                        material={material}
                                        hrefs={hrefs}
                                        onDelete={onDelete}
                                    />
                                </TableCell>
                            </TableRow>
                        ))}
                    </TableBody>
                </Table>
            </div>

            {/* < md: stacked cards */}
            <ul aria-label="Materials" className="space-y-3 md:hidden">
                {materials.map((material) => (
                    <li
                        key={material.id}
                        className="space-y-3 rounded-lg border bg-card p-4 text-card-foreground"
                    >
                        <div className="flex items-start justify-between gap-3">
                            <div className="min-w-0">
                                <Link
                                    href={hrefs.show(material)}
                                    className="font-medium break-words hover:underline focus-visible:underline"
                                >
                                    {material.name}
                                </Link>
                                <p className="text-xs text-muted-foreground">
                                    <span className="font-mono">
                                        {material.sku}
                                    </span>
                                    {' · '}
                                    {material.category_label}
                                </p>
                            </div>
                            <StockBadge status={material.stock_status} />
                        </div>
                        <dl className="grid grid-cols-3 gap-2 text-sm">
                            <div>
                                <dt className="text-xs text-muted-foreground">
                                    Stock
                                </dt>
                                <dd className="tabular-nums">
                                    <Quantity
                                        value={material.quantity_on_hand}
                                        unit={material.unit}
                                    />
                                </dd>
                            </div>
                            <div>
                                <dt className="text-xs text-muted-foreground">
                                    Unit cost
                                </dt>
                                <dd className="tabular-nums">
                                    <UnitCost material={material} />
                                </dd>
                            </div>
                            <div>
                                <dt className="text-xs text-muted-foreground">
                                    Stock value
                                </dt>
                                <dd className="tabular-nums">
                                    <Money value={material.stock_value} />
                                </dd>
                            </div>
                        </dl>
                        <TextActions
                            material={material}
                            hrefs={hrefs}
                            onDelete={onDelete}
                        />
                    </li>
                ))}
            </ul>
        </>
    );
}

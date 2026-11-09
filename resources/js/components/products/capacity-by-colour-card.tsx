import { Link } from '@inertiajs/react';
import { ColourTag } from '@/components/shared/colour-tag';
import { SkeletonCard } from '@/components/shared/skeletons';
import {
    Table,
    TableBody,
    TableCaption,
    TableCell,
    TableFooter,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { useAppSettings } from '@/hooks/use-app-settings';
import { EMPTY_VALUE, formatCount } from '@/lib/format';
import type { ColourCapacity, MaterialRef } from '@/types/domain';

type CapacityByColourCardProps = {
    /** `product.capacity_by_colour`: one row per colour, in the product's colour order. */
    rows: ColourCapacity[];
    /** `product.capacity.max_producible`: the guaranteed figure of any mix of colours. */
    guaranteed: number | null;
    /** `product.capacity.best`: the most one colour and size makes on its own. */
    best: number | null;
    /** URL builder for a material (Wayfinder in the page). */
    materialHref: (material: MaterialRef) => string;
};

const TOTAL_LABEL = 'All colours (any mix)';

/** A colour that uses no material at all: it counts for neither figure. */
function NoComposition() {
    return <span className="text-muted-foreground">No composition</span>;
}

/** The limiting material, linked, with the size that binds it: "Denim 12oz Indigo (XL)". */
function LimitedBy({
    row,
    materialHref,
}: {
    row: ColourCapacity;
    materialHref: CapacityByColourCardProps['materialHref'];
}) {
    if (row.bottleneck === null) {
        return <span className="text-muted-foreground">{EMPTY_VALUE}</span>;
    }

    return (
        <span className="[overflow-wrap:anywhere]">
            <Link
                href={materialHref(row.bottleneck)}
                className="font-semibold hover:underline focus-visible:underline"
            >
                {row.bottleneck.name}
            </Link>
            {row.binding_size && ` (${row.binding_size.label})`}
        </span>
    );
}

function Figure({ value }: { value: number | null }) {
    const { locale } = useAppSettings();

    return (
        <strong className="tabular-nums">
            {value === null ? EMPTY_VALUE : formatCount(value, locale)}
        </strong>
    );
}

/** The card while the product's figures load: the same title and a few placeholder rows. */
export function CapacityByColourCardLoading() {
    return (
        <SkeletonCard
            title="Capacity by colour"
            label="capacity by colour"
            rows={2}
            thumb={false}
        />
    );
}

/**
 * "Capacity by colour" (INV-16): for each colour of a product with a By colour
 * and size line, the most any size mix can make (guaranteed), the most one size
 * can make on its own (best) and the material that limits it, then the same two
 * figures for any mix of colours. A table from `md` up, one stacked card per
 * colour below. Every figure is the server's.
 */
export function CapacityByColourCard({
    rows,
    guaranteed,
    best,
    materialHref,
}: CapacityByColourCardProps) {
    return (
        <section
            aria-labelledby="capacity-by-colour-title"
            className="overflow-hidden rounded-xl border bg-card text-card-foreground"
        >
            <h2
                id="capacity-by-colour-title"
                className="px-4 pt-5 pb-3 text-lg font-bold md:px-5"
            >
                Capacity by colour
            </h2>

            {/* >= md: table */}
            <div className="hidden md:block">
                <Table>
                    <TableCaption className="sr-only">
                        Capacity by colour
                    </TableCaption>
                    <TableHeader>
                        <TableRow>
                            <TableHead scope="col" className="pl-5">
                                Colour
                            </TableHead>
                            <TableHead scope="col" className="text-right">
                                Guaranteed
                            </TableHead>
                            <TableHead scope="col" className="text-right">
                                Best
                            </TableHead>
                            <TableHead scope="col" className="pr-5">
                                Limited by
                            </TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        {rows.map((row) => (
                            <TableRow key={row.colour.id}>
                                <TableHead scope="row" className="pl-5">
                                    <ColourTag colour={row.colour} />
                                </TableHead>
                                {row.guaranteed === null ? (
                                    <TableCell colSpan={3} className="pr-5">
                                        <NoComposition />
                                    </TableCell>
                                ) : (
                                    <>
                                        <TableCell className="text-right">
                                            <Figure value={row.guaranteed} />
                                        </TableCell>
                                        <TableCell className="text-right">
                                            <Figure value={row.best} />
                                        </TableCell>
                                        <TableCell className="pr-5 whitespace-normal">
                                            <LimitedBy
                                                row={row}
                                                materialHref={materialHref}
                                            />
                                        </TableCell>
                                    </>
                                )}
                            </TableRow>
                        ))}
                    </TableBody>
                    <TableFooter>
                        <TableRow>
                            <TableHead scope="row" className="pl-5 font-bold">
                                {TOTAL_LABEL}
                            </TableHead>
                            {guaranteed === null ? (
                                <TableCell colSpan={3} className="pr-5">
                                    <NoComposition />
                                </TableCell>
                            ) : (
                                <>
                                    <TableCell className="text-right">
                                        <Figure value={guaranteed} />
                                    </TableCell>
                                    <TableCell className="text-right">
                                        <Figure value={best} />
                                    </TableCell>
                                    <TableCell className="pr-5" />
                                </>
                            )}
                        </TableRow>
                    </TableFooter>
                </Table>
            </div>

            {/* < md: one stacked card per colour */}
            <div className="md:hidden">
                <ul
                    aria-label="Capacity by colour"
                    className="space-y-3 px-4 pb-4"
                >
                    {rows.map((row) => (
                        <li
                            key={row.colour.id}
                            className="space-y-3 rounded-lg border p-4"
                        >
                            <ColourTag
                                colour={row.colour}
                                className="font-semibold"
                            />
                            {row.guaranteed === null ? (
                                <p className="text-sm">
                                    <NoComposition />
                                </p>
                            ) : (
                                <dl className="grid grid-cols-2 gap-x-3 gap-y-2 text-sm">
                                    <div>
                                        <dt className="text-xs text-muted-foreground">
                                            Guaranteed
                                        </dt>
                                        <dd>
                                            <Figure value={row.guaranteed} />
                                        </dd>
                                    </div>
                                    <div>
                                        <dt className="text-xs text-muted-foreground">
                                            Best
                                        </dt>
                                        <dd>
                                            <Figure value={row.best} />
                                        </dd>
                                    </div>
                                    <div className="col-span-2">
                                        <dt className="text-xs text-muted-foreground">
                                            Limited by
                                        </dt>
                                        <dd>
                                            <LimitedBy
                                                row={row}
                                                materialHref={materialHref}
                                            />
                                        </dd>
                                    </div>
                                </dl>
                            )}
                        </li>
                    ))}
                </ul>
                <div
                    role="group"
                    aria-label={TOTAL_LABEL}
                    className="mx-4 mb-4 space-y-2 rounded-lg bg-muted/50 p-4 text-sm"
                >
                    <p className="font-bold">{TOTAL_LABEL}</p>
                    {guaranteed === null ? (
                        <p>
                            <NoComposition />
                        </p>
                    ) : (
                        <dl className="grid grid-cols-2 gap-x-3">
                            <div>
                                <dt className="text-xs text-muted-foreground">
                                    Guaranteed
                                </dt>
                                <dd>
                                    <Figure value={guaranteed} />
                                </dd>
                            </div>
                            <div>
                                <dt className="text-xs text-muted-foreground">
                                    Best
                                </dt>
                                <dd>
                                    <Figure value={best} />
                                </dd>
                            </div>
                        </dl>
                    )}
                </div>
            </div>
        </section>
    );
}

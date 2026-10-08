import type { ReactNode } from 'react';
import { useAppSettings } from '@/hooks/use-app-settings';
import { EMPTY_VALUE, formatDate } from '@/lib/format';
import { cn } from '@/lib/utils';
import type { Material } from '@/types/domain';

/** Optional text: the value, or an em dash. */
function orDash(value: string | null): string {
    return value === null || value.trim() === '' ? EMPTY_VALUE : value;
}

function Fact({
    label,
    children,
    className,
}: {
    label: string;
    children: ReactNode;
    className?: string;
}) {
    return (
        <>
            <dt className="border-t py-2.5 font-medium text-muted-foreground">
                {label}
            </dt>
            <dd className={cn('border-t py-2.5 break-words', className)}>
                {children}
            </dd>
        </>
    );
}

/** Unit, supplier, colour, composition, notes and when it last changed. */
export function MaterialDetailsCard({ material }: { material: Material }) {
    const { locale } = useAppSettings();

    return (
        <section
            aria-labelledby="material-details-title"
            className="rounded-xl border bg-card px-5 py-2 text-card-foreground shadow-sm"
        >
            <h2 id="material-details-title" className="sr-only">
                Details
            </h2>
            <dl className="grid grid-cols-[minmax(6rem,10rem)_minmax(0,1fr)] gap-x-4 text-sm [&>:nth-child(-n+2)]:border-t-0">
                <Fact label="Unit">{material.unit_label}</Fact>
                <Fact label="Supplier">{orDash(material.supplier_name)}</Fact>
                <Fact label="Supplier ref." className="font-mono text-[13px]">
                    {orDash(material.supplier_reference)}
                </Fact>
                <Fact label="Colour">{orDash(material.color)}</Fact>
                <Fact label="Composition">{orDash(material.composition)}</Fact>
                <Fact label="Notes">
                    <span className="whitespace-pre-line">
                        {orDash(material.notes)}
                    </span>
                </Fact>
                <Fact label="Last updated">
                    {formatDate(material.updated_at, locale)}
                </Fact>
            </dl>
        </section>
    );
}

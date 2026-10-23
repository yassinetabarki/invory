import { useAppSettings } from '@/hooks/use-app-settings';
import { formatCount } from '@/lib/format';
import { formatSizes } from '@/lib/sizes';
import type { SizeCount } from '@/lib/sizes';
import { cn } from '@/lib/utils';

type SizeChipsProps = {
    sizes: SizeCount[];
    /** Keep sizes with 0 units, greyed (only the "In stock" figure does). */
    keepZeros?: boolean;
    /** Words before the spoken list, for example "In stock per size: ". */
    spokenPrefix?: string;
    className?: string;
};

/**
 * One chip per size: the bare label in bold on grey, its count beside it in the number
 * font ("S 10", a shoe size "36 | 4"). The chips wrap, so 11 shoe sizes never scroll
 * sideways. Screen readers skip the chips and hear one line instead ("S 10, M 20", a
 * shoe size "36: 4"). Sizes with 0 units are left out unless `keepZeros` is set.
 */
export function SizeChips({
    sizes,
    keepZeros = false,
    spokenPrefix = '',
    className,
}: SizeChipsProps) {
    const { locale } = useAppSettings();
    const shown = sizes.filter(({ units }) => keepZeros || units > 0);

    if (shown.length === 0) {
        return null;
    }

    return (
        <>
            <p className="sr-only">
                {spokenPrefix}
                {formatSizes(shown, locale, { keepZeros, separator: ', ' })}
            </p>
            <div
                aria-hidden="true"
                className={cn('flex flex-wrap gap-1.5', className)}
            >
                {shown.map(({ label, units }) => (
                    <span
                        key={label}
                        data-zero={units === 0 ? '' : undefined}
                        className="inline-flex items-stretch overflow-hidden rounded-md border text-sm leading-none"
                    >
                        <b className="bg-muted px-2 py-1.5 font-semibold">
                            {label}
                        </b>
                        <span
                            className={cn(
                                'px-2 py-1.5 font-mono text-[0.8125rem] tabular-nums',
                                units === 0
                                    ? 'font-normal text-muted-foreground'
                                    : 'font-semibold',
                            )}
                        >
                            {formatCount(units, locale)}
                        </span>
                    </span>
                ))}
            </div>
        </>
    );
}

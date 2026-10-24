import type { ComponentProps } from 'react';
import { Button } from '@/components/ui/button';
import { cn } from '@/lib/utils';

/**
 * A move button of the order page. The generated Button fades a disabled button
 * to half opacity (a pale blue fill, an outline that nearly vanishes); this one
 * shows a disabled button as a plain grey one on either variant. The generated
 * file is never edited by hand, so the look is scoped here, to these buttons.
 * Enabled buttons keep exactly their variant classes.
 */
export function MoveButton({
    className,
    ...props
}: ComponentProps<typeof Button>) {
    return (
        <Button
            className={cn(
                'disabled:border disabled:border-border disabled:bg-muted disabled:text-muted-foreground disabled:opacity-100 disabled:shadow-none',
                className,
            )}
            {...props}
        />
    );
}

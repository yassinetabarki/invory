import { useId } from 'react';
import type { ComponentProps } from 'react';
import { Button } from '@/components/ui/button';
import {
    Tooltip,
    TooltipContent,
    TooltipTrigger,
} from '@/components/ui/tooltip';
import { cn } from '@/lib/utils';

type ReasonedButtonProps = ComponentProps<typeof Button> & {
    /** Why the button can't be pressed now; null when it can. */
    reason: string | null;
};

/**
 * A button that can't be pressed while it has a reason (INV-5): it stays in the
 * tab order (`aria-disabled`, not `disabled`), so the keyboard reaches it and
 * its tip, and the reason is also its accessible description.
 */
export function ReasonedButton({
    reason,
    onClick,
    className,
    ...props
}: ReasonedButtonProps) {
    const id = useId();

    if (reason === null) {
        return <Button onClick={onClick} className={className} {...props} />;
    }

    return (
        <Tooltip>
            <TooltipTrigger asChild>
                <Button
                    {...props}
                    aria-disabled
                    aria-describedby={`${id}-reason`}
                    className={cn(
                        'aria-disabled:cursor-not-allowed aria-disabled:opacity-50',
                        className,
                    )}
                    onClick={(event) => event.preventDefault()}
                />
            </TooltipTrigger>
            <TooltipContent>{reason}</TooltipContent>
            <span id={`${id}-reason`} className="sr-only">
                {reason}
            </span>
        </Tooltip>
    );
}

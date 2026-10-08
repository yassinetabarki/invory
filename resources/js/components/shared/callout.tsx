import { CircleCheck, Info, OctagonAlert, TriangleAlert } from 'lucide-react';
import type { LucideIcon } from 'lucide-react';
import type { ReactNode } from 'react';
import { cn } from '@/lib/utils';

type CalloutTone = 'info' | 'warning' | 'danger' | 'success';
type CalloutRole = 'alert' | 'note' | 'status';

type CalloutProps = {
    tone: CalloutTone;
    /** Bold lead-in, e.g. "Canvas Low Sneaker can't be produced." */
    title?: ReactNode;
    children: ReactNode;
    /** A link or button on the right, e.g. "See product". */
    action?: ReactNode;
    /**
     * `alert` interrupts screen readers, `status` announces politely, `note`
     * is static content. Defaults: danger `alert`, success `status`, others `note`.
     */
    role?: CalloutRole;
    className?: string;
};

const TONE: Record<CalloutTone, { icon: LucideIcon; className: string }> = {
    info: { icon: Info, className: 'bg-info text-info-foreground' },
    warning: {
        icon: TriangleAlert,
        className: 'bg-warning text-warning-foreground',
    },
    danger: {
        icon: OctagonAlert,
        className: 'bg-danger text-danger-foreground',
    },
    success: {
        icon: CircleCheck,
        className: 'bg-success text-success-foreground',
    },
};

const DEFAULT_ROLE: Record<CalloutTone, CalloutRole> = {
    info: 'note',
    warning: 'note',
    danger: 'alert',
    success: 'status',
};

/**
 * A highlighted message with an icon per tone (the icon plus the text carry
 * the meaning, not the colour alone) and an optional action.
 */
export function Callout({
    tone,
    title,
    children,
    action,
    role,
    className,
}: CalloutProps) {
    const { icon: Icon, className: toneClass } = TONE[tone];

    return (
        <div
            role={role ?? DEFAULT_ROLE[tone]}
            data-slot="callout"
            data-tone={tone}
            className={cn(
                'flex flex-wrap items-start gap-x-3.5 gap-y-2.5 rounded-lg px-4 py-3 text-sm',
                toneClass,
                className,
            )}
        >
            <Icon className="mt-0.5 size-5 shrink-0" aria-hidden="true" />
            <div className="min-w-0 flex-1 basis-72">
                {title && <strong className="font-semibold">{title} </strong>}
                {children}
            </div>
            {action && (
                <div className="shrink-0 font-semibold [&_a]:underline [&_a]:underline-offset-2">
                    {action}
                </div>
            )}
        </div>
    );
}

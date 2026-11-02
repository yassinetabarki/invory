import { Check } from 'lucide-react';
import { SOURCE_ICONS } from '@/components/clients/source-label';
import { cn } from '@/lib/utils';
import type { ClientSource, Option } from '@/types/domain';

type SourceChipsProps = {
    /** Id of the visible label the group is named by ("Source"). */
    labelledBy: string;
    describedBy?: string;
    options: Option<ClientSource>[];
    value: ClientSource | '';
    onChange: (value: ClientSource | '') => void;
};

/**
 * The source as a row of chips (INV-4, AC-1): one choice, nothing chosen at
 * first, and a second tap on the chosen chip un-chooses it. The chosen chip
 * shows a check in place of its icon, a 2 px Denim border and Denim text.
 * Screen readers hear one group named "Source", each chip a toggle button.
 */
export function SourceChips({
    labelledBy,
    describedBy,
    options,
    value,
    onChange,
}: SourceChipsProps) {
    return (
        <div
            role="group"
            aria-labelledby={labelledBy}
            aria-describedby={describedBy}
            className="grid grid-cols-2 gap-2 min-[400px]:grid-cols-3 sm:flex sm:flex-wrap"
        >
            {options.map((option) => {
                const chosen = option.value === value;
                const Icon = chosen ? Check : SOURCE_ICONS[option.value];

                return (
                    <button
                        key={option.value}
                        type="button"
                        aria-pressed={chosen}
                        onClick={() => onChange(chosen ? '' : option.value)}
                        className={cn(
                            'inline-flex h-11 min-w-0 items-center justify-center gap-1.5 rounded-full border bg-background px-3 text-sm outline-none focus-visible:ring-[3px] focus-visible:ring-ring/50 sm:h-8',
                            chosen
                                ? 'border-2 border-primary font-medium text-primary'
                                : 'border-input text-foreground hover:bg-muted',
                        )}
                    >
                        <Icon
                            className="size-3.5 shrink-0"
                            aria-hidden="true"
                        />
                        <span className="truncate">{option.label}</span>
                    </button>
                );
            })}
        </div>
    );
}

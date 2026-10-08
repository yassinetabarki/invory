import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import type { Option, SalesPeriod } from '@/types/domain';

/** The period the server uses when the query string has none (and so omits). */
export const DEFAULT_PERIOD: SalesPeriod = 'last_30_days';

type PeriodSelectProps = {
    /** Id of the trigger, so the visually hidden label points at it. */
    id: string;
    value: SalesPeriod;
    /** Options from the server (`periods` prop); labels are never hard-coded. */
    periods: Option<SalesPeriod>[];
    onChange: (period: SalesPeriod) => void;
    className?: string;
};

/**
 * Period picker shared by the dashboard and the sales log. The "Period" label
 * is visually hidden: the selected option already says what the control does.
 */
export function PeriodSelect({
    id,
    value,
    periods,
    onChange,
    className,
}: PeriodSelectProps) {
    return (
        <div className={className}>
            <Label htmlFor={id} className="sr-only">
                Period
            </Label>
            <Select
                value={value}
                onValueChange={(next) => {
                    const option = periods.find(
                        (period) => period.value === next,
                    );

                    if (option) {
                        onChange(option.value);
                    }
                }}
            >
                <SelectTrigger id={id} className="w-full">
                    <SelectValue />
                </SelectTrigger>
                <SelectContent>
                    {periods.map((period) => (
                        <SelectItem key={period.value} value={period.value}>
                            {period.label}
                        </SelectItem>
                    ))}
                </SelectContent>
            </Select>
        </div>
    );
}

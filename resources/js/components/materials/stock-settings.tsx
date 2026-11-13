import { useId } from 'react';
import type { MaterialCostMode, MaterialStockMode } from '@/types/domain';

type ModeOption<T extends string> = {
    value: T;
    label: string;
    description: string;
};

const STOCK_OPTIONS: ModeOption<MaterialStockMode>[] = [
    {
        value: 'single',
        label: 'One stock for all colours',
        description:
            'For buttons, thread, labels. One quantity, whatever the colour.',
    },
    {
        value: 'per_colour',
        label: 'Stock per colour',
        description:
            'For fabric and leather. A quantity for each colour, such as Indigo 250 m and Black 40 m.',
    },
];

const COST_OPTIONS: ModeOption<MaterialCostMode>[] = [
    {
        value: 'same',
        label: 'Same cost for every colour',
        description: 'One unit cost for the whole material.',
    },
    {
        value: 'per_colour',
        label: 'Cost per colour',
        description:
            'Each colour has its own unit cost, such as Indigo TND 8.500 and Black TND 9.200.',
    },
];

function ModeCard<T extends string>({
    name,
    option,
    checked,
    onChange,
}: {
    name: string;
    option: ModeOption<T>;
    checked: boolean;
    onChange: (value: T) => void;
}) {
    const labelId = useId();
    const descriptionId = useId();

    return (
        <label className="flex min-h-11 cursor-pointer items-start gap-3 rounded-lg border p-3 has-checked:border-primary has-checked:bg-accent has-focus-visible:ring-[3px] has-focus-visible:ring-ring/50">
            <input
                type="radio"
                name={name}
                value={option.value}
                checked={checked}
                onChange={() => onChange(option.value)}
                aria-labelledby={labelId}
                aria-describedby={descriptionId}
                className="mt-0.5 size-4 shrink-0 accent-primary outline-none"
            />
            <span className="min-w-0 space-y-0.5">
                <span id={labelId} className="block text-sm font-medium">
                    {option.label}
                </span>
                <span
                    id={descriptionId}
                    className="block text-xs text-muted-foreground"
                >
                    {option.description}
                </span>
            </span>
        </label>
    );
}

function ModeGroup<T extends string>({
    legend,
    name,
    options,
    value,
    onChange,
}: {
    legend: string;
    name: string;
    options: ModeOption<T>[];
    value: T;
    onChange: (value: T) => void;
}) {
    return (
        <fieldset className="min-w-0 space-y-2">
            <legend className="text-sm leading-none font-medium">
                {legend}
            </legend>
            <div className="grid gap-2 sm:grid-cols-2">
                {options.map((option) => (
                    <ModeCard
                        key={option.value}
                        name={name}
                        option={option}
                        checked={value === option.value}
                        onChange={onChange}
                    />
                ))}
            </div>
        </fieldset>
    );
}

type StockSettingsProps = {
    stockMode: MaterialStockMode;
    costMode: MaterialCostMode;
    onStockModeChange: (mode: MaterialStockMode) => void;
    onCostModeChange: (mode: MaterialCostMode) => void;
};

/**
 * "How is stock kept?" and, only with Stock per colour, "How is cost set?",
 * as radio cards. The radios are native and named `stock_mode` / `cost_mode`,
 * so the browser form submits the choice. The parent decides what a change does.
 */
export function StockSettings({
    stockMode,
    costMode,
    onStockModeChange,
    onCostModeChange,
}: StockSettingsProps) {
    return (
        <div className="space-y-4">
            <ModeGroup
                legend="How is stock kept?"
                name="stock_mode"
                options={STOCK_OPTIONS}
                value={stockMode}
                onChange={onStockModeChange}
            />
            {stockMode === 'per_colour' && (
                <ModeGroup
                    legend="How is cost set?"
                    name="cost_mode"
                    options={COST_OPTIONS}
                    value={costMode}
                    onChange={onCostModeChange}
                />
            )}
        </div>
    );
}

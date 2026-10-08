import { useId, useState } from 'react';

export type PanelKind = 'restock' | 'history';

/** Props for the button that opens a panel (`aria-expanded` + `aria-controls`). */
export type PanelTriggerProps = {
    id: string;
    'aria-expanded': boolean;
    'aria-controls': string | undefined;
    onClick: () => void;
};

export type RowPanels = {
    /** The panel open for a row, if any (one panel per row at a time). */
    openKind: (rowId: number) => PanelKind | undefined;
    triggerProps: (rowId: number, kind: PanelKind) => PanelTriggerProps;
    panelId: (rowId: number, kind: PanelKind) => string;
    /** Closes the row's panel and returns focus to the button that opened it. */
    close: (rowId: number) => void;
};

/**
 * Which inline panel (restock or history) is open in which row. A row has at
 * most one panel: opening the other kind replaces it, and the same button
 * toggles it. Ids are unique per hook instance, so the table and the cards
 * (both in the DOM, one hidden by CSS) never share an id.
 */
export function useRowPanels(): RowPanels {
    const uid = useId();
    const [open, setOpen] = useState<Partial<Record<number, PanelKind>>>({});

    const triggerId = (rowId: number, kind: PanelKind) =>
        `${uid}-trigger-${rowId}-${kind}`;
    const panelId = (rowId: number, kind: PanelKind) =>
        `${uid}-panel-${rowId}-${kind}`;

    function withoutRow(
        current: Partial<Record<number, PanelKind>>,
        rowId: number,
    ) {
        const next = { ...current };

        delete next[rowId];

        return next;
    }

    return {
        openKind: (rowId) => open[rowId],
        panelId,
        triggerProps: (rowId, kind) => {
            const expanded = open[rowId] === kind;

            return {
                id: triggerId(rowId, kind),
                'aria-expanded': expanded,
                'aria-controls': expanded ? panelId(rowId, kind) : undefined,
                onClick: () =>
                    setOpen((current) =>
                        current[rowId] === kind
                            ? withoutRow(current, rowId)
                            : { ...current, [rowId]: kind },
                    ),
            };
        },
        close: (rowId) => {
            const kind = open[rowId];

            if (kind === undefined) {
                return;
            }

            // Focus first: the panel (and any focus inside it) is about to go.
            document.getElementById(triggerId(rowId, kind))?.focus();
            setOpen((current) => withoutRow(current, rowId));
        },
    };
}

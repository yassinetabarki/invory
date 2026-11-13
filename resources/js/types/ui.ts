import type { ReactNode } from 'react';
import type { BreadcrumbItem } from '@/types/navigation';

export type AppLayoutProps = {
    children: ReactNode;
    breadcrumbs?: BreadcrumbItem[];
};

export type AppVariant = 'header' | 'sidebar';

/** Opens the Restock sheet over the current page on one colour of a per-colour material (INV-21). */
export type RestockSheetRequest = {
    material_id: number;
    colour_id: number;
    /** For the failure toast: "Couldn't open Restock for Denim 12oz. Try again." */
    material_name: string;
};

export type FlashToast = {
    type: 'success' | 'info' | 'warning' | 'error';
    message: string;
    // a button in the toast: a link, e.g. "See the restock" after Put in stock (INV-5); `new_tab` opens it in a new tab, e.g. "Print ticket B-0155"
    // after a split (INV-15); or `restock`, which opens the Restock sheet on that colour ("Restock Light wash" after a colour is added, INV-21)
    action?: { label: string } & (
        | { href: string; new_tab?: boolean; restock?: never }
        | { href?: never; new_tab?: never; restock: RestockSheetRequest }
    );
};

export type AuthLayoutProps = {
    children?: ReactNode;
    name?: string;
    title?: string;
    description?: string;
};

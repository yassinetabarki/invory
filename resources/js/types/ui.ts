import type { ReactNode } from 'react';
import type { BreadcrumbItem } from '@/types/navigation';

export type AppLayoutProps = {
    children: ReactNode;
    breadcrumbs?: BreadcrumbItem[];
};

export type AppVariant = 'header' | 'sidebar';

export type FlashToast = {
    type: 'success' | 'info' | 'warning' | 'error';
    message: string;
    // a link in the toast, e.g. "See the restock" after Put in stock (INV-5); `new_tab` opens it in a new tab, e.g. "Print ticket B-0155"
    // after a split (INV-15)
    action?: { label: string; href: string; new_tab?: boolean };
};

export type AuthLayoutProps = {
    children?: ReactNode;
    name?: string;
    title?: string;
    description?: string;
};

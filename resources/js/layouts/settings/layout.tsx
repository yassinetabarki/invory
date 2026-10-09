import { Link } from '@inertiajs/react';
import type { PropsWithChildren } from 'react';
import Heading from '@/components/heading';
import { Button } from '@/components/ui/button';
import { Separator } from '@/components/ui/separator';
import { useCurrentUrl } from '@/hooks/use-current-url';
import { cn, toUrl } from '@/lib/utils';
import { edit as editAppearance } from '@/routes/appearance';
import { edit } from '@/routes/profile';
import { edit as editSecurity } from '@/routes/security';
import { index as materialCategoriesIndex } from '@/routes/settings/material-categories';
import { index as productCategoriesIndex } from '@/routes/settings/product-categories';
import { index as unitsIndex } from '@/routes/settings/units';
import type { NavItem } from '@/types';

const sidebarNavItems: NavItem[] = [
    {
        title: 'Profile',
        href: edit(),
        icon: null,
    },
    {
        title: 'Security',
        href: editSecurity(),
        icon: null,
    },
    {
        title: 'Appearance',
        href: editAppearance(),
        icon: null,
    },
];

/** Company reference data (ADR-0008): the units and categories the forms pick from. */
const catalogueNavItems: NavItem[] = [
    {
        title: 'Units',
        href: unitsIndex(),
        icon: null,
    },
    {
        title: 'Material categories',
        href: materialCategoriesIndex(),
        icon: null,
    },
    {
        title: 'Product categories',
        href: productCategoriesIndex(),
        icon: null,
    },
];

export default function SettingsLayout({ children }: PropsWithChildren) {
    const { isCurrentOrParentUrl } = useCurrentUrl();

    // The catalogue pages hold tables, so they get the full width of the page
    // instead of the narrow column the profile forms use.
    const wide = catalogueNavItems.some((item) =>
        isCurrentOrParentUrl(item.href),
    );

    function renderItems(items: NavItem[]) {
        return items.map((item) => {
            const current = isCurrentOrParentUrl(item.href);

            return (
                <Button
                    key={toUrl(item.href)}
                    size="sm"
                    variant="ghost"
                    asChild
                    className={cn('w-full justify-start', {
                        'bg-muted': current,
                    })}
                >
                    <Link
                        href={item.href}
                        aria-current={current ? 'page' : undefined}
                    >
                        {item.icon && <item.icon className="h-4 w-4" />}
                        {item.title}
                    </Link>
                </Button>
            );
        });
    }

    return (
        <div className="px-4 py-6">
            <Heading
                title="Settings"
                description="Manage your profile and account settings"
            />

            <div className="flex flex-col lg:flex-row lg:space-x-12">
                <aside className="w-full max-w-xl lg:w-48 lg:shrink-0">
                    <nav
                        className="flex flex-col space-y-1 space-x-0"
                        aria-label="Settings"
                    >
                        {renderItems(sidebarNavItems)}

                        <div
                            role="group"
                            aria-labelledby="settings-catalogue-heading"
                            className="flex flex-col space-y-1 pt-4"
                        >
                            <h3
                                id="settings-catalogue-heading"
                                className="px-3 text-xs font-medium tracking-wide text-muted-foreground uppercase"
                            >
                                Catalogue
                            </h3>
                            {renderItems(catalogueNavItems)}
                        </div>
                    </nav>
                </aside>

                <Separator className="my-6 lg:hidden" />

                <div className={cn('min-w-0 flex-1', !wide && 'md:max-w-2xl')}>
                    <section className={cn('space-y-12', !wide && 'max-w-xl')}>
                        {children}
                    </section>
                </div>
            </div>
        </div>
    );
}

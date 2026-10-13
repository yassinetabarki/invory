import { Link } from '@inertiajs/react';
import {
    SidebarGroup,
    SidebarGroupLabel,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { useCurrentUrl } from '@/hooks/use-current-url';
import { toUrl } from '@/lib/utils';
import { dashboard } from '@/routes';
import type { NavItem } from '@/types';

type NavMatch = 'exact' | 'parent' | null;

/** `https://x.test/materials/5?tab=1#top` → `/materials/5`; `/` stays `/`. */
function pathnameOf(url: string): string {
    const path = url
        .replace(/^[a-z][a-z\d+.-]*:\/\/[^/]+/i, '')
        .split(/[?#]/)[0];

    return path.length > 1 ? path.replace(/\/+$/, '') : path || '/';
}

/**
 * How the current page relates to a nav item (F-01). Dashboard is the home
 * screen and only matches its own URL. Every other item matches its own URL
 * (`exact`) and the pages beneath it (`parent`): `/materials/5/edit` highlights
 * Materials, while `/materials-archive` does not.
 */
function matchNavItem(item: NavItem, currentPath: string): NavMatch {
    const itemPath = pathnameOf(toUrl(item.href));
    const current = pathnameOf(currentPath);

    if (current === itemPath) {
        return 'exact';
    }

    const isHome = itemPath === '/' || itemPath === pathnameOf(dashboard().url);

    return !isHome && current.startsWith(`${itemPath}/`) ? 'parent' : null;
}

export function NavMain({ items }: { items: NavItem[] }) {
    const { currentUrl } = useCurrentUrl();

    return (
        <SidebarGroup className="px-2 py-0">
            <SidebarGroupLabel>Platform</SidebarGroupLabel>
            <SidebarMenu>
                {items.map((item) => {
                    const match = matchNavItem(item, currentUrl);

                    return (
                        <SidebarMenuItem key={item.title}>
                            <SidebarMenuButton
                                asChild
                                isActive={match !== null}
                                tooltip={{ children: item.title }}
                                className="font-medium data-[active=true]:bg-sidebar-active data-[active=true]:shadow-[0_0_0_1px_var(--sidebar-border)]"
                            >
                                <Link
                                    href={item.href}
                                    aria-current={
                                        match === 'exact'
                                            ? 'page'
                                            : match === 'parent'
                                              ? 'true'
                                              : undefined
                                    }
                                >
                                    {item.icon && <item.icon />}
                                    <span>{item.title}</span>
                                </Link>
                            </SidebarMenuButton>
                        </SidebarMenuItem>
                    );
                })}
            </SidebarMenu>
        </SidebarGroup>
    );
}

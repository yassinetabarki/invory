import { Link } from '@inertiajs/react';
import { Boxes, LayoutGrid, ReceiptText, Shirt } from 'lucide-react';
import AppLogo from '@/components/app-logo';
import { NavMain } from '@/components/nav-main';
import { NavUser } from '@/components/nav-user';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { CACHE_TAGS, prefetchProps } from '@/lib/cache-tags';
import { dashboard } from '@/routes';
import { index as materialsIndex } from '@/routes/materials';
import { index as productsIndex } from '@/routes/products';
import { index as salesIndex } from '@/routes/sales';
import type { NavItem } from '@/types';

const mainNavItems: NavItem[] = [
    {
        title: 'Dashboard',
        href: dashboard(),
        icon: LayoutGrid,
    },
    {
        title: 'Materials',
        href: materialsIndex(),
        icon: Boxes,
    },
    {
        title: 'Products',
        href: productsIndex(),
        icon: Shirt,
    },
    {
        title: 'Sales',
        href: salesIndex(),
        icon: ReceiptText,
    },
];

export function AppSidebar() {
    return (
        <Sidebar collapsible="icon" variant="inset">
            <SidebarHeader>
                <SidebarMenu>
                    <SidebarMenuItem>
                        <SidebarMenuButton size="lg" asChild>
                            <Link
                                href={dashboard()}
                                {...prefetchProps(CACHE_TAGS.dashboard)}
                            >
                                <AppLogo />
                            </Link>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                </SidebarMenu>
            </SidebarHeader>

            <SidebarContent>
                <NavMain items={mainNavItems} />
            </SidebarContent>

            <SidebarFooter>
                <NavUser />
            </SidebarFooter>
        </Sidebar>
    );
}

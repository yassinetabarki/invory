import { Link } from '@inertiajs/react';
import {
    Boxes,
    ClipboardList,
    Factory,
    LayoutGrid,
    PackagePlus,
    ReceiptText,
    ScanQrCode,
    Shirt,
    Users,
} from 'lucide-react';
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
import { dashboard } from '@/routes';
import { index as clientsIndex } from '@/routes/clients';
import { index as materialsIndex } from '@/routes/materials';
import { index as ordersIndex } from '@/routes/orders';
import { index as productsIndex } from '@/routes/products';
import { index as progressIndex } from '@/routes/progress';
import { index as restocksIndex } from '@/routes/restocks';
import { index as salesIndex } from '@/routes/sales';
import { index as scanIndex } from '@/routes/scan';
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
        title: 'Restocks',
        href: restocksIndex(),
        icon: PackagePlus,
    },
    {
        title: 'Orders',
        href: ordersIndex(),
        icon: ClipboardList,
    },
    {
        title: 'Progress',
        href: progressIndex(),
        icon: Factory,
    },
    {
        title: 'Scan',
        href: scanIndex(),
        icon: ScanQrCode,
    },
    {
        title: 'Clients',
        href: clientsIndex(),
        icon: Users,
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
                            <Link href={dashboard()}>
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

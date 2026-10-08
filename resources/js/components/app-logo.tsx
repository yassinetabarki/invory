import AppLogoIcon from '@/components/app-logo-icon';

/** Sidebar brand: a primary-coloured tile with the white "I" mark, then the app name. */
export default function AppLogo() {
    return (
        <>
            <div className="flex aspect-square size-8 shrink-0 items-center justify-center rounded-lg bg-sidebar-primary text-sidebar-primary-foreground">
                <AppLogoIcon className="size-5 fill-current" />
            </div>
            <div className="ml-1 grid flex-1 text-left text-sm">
                <span className="truncate text-lg leading-tight font-bold tracking-tight">
                    Invory
                </span>
            </div>
        </>
    );
}

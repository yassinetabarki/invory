import { Head, Link, usePage } from '@inertiajs/react';
import AppLogoIcon from '@/components/app-logo-icon';
import { Button } from '@/components/ui/button';
import { dashboard, login, register } from '@/routes';

export default function Welcome() {
    const { auth } = usePage().props;

    return (
        <>
            <Head title="Welcome" />
            <div className="flex min-h-screen flex-col bg-background text-foreground">
                <header className="flex items-center justify-between gap-4 px-4 py-4 sm:px-6 lg:px-8">
                    <div className="flex items-center gap-2">
                        <div className="flex size-8 items-center justify-center rounded-md bg-primary text-primary-foreground">
                            <AppLogoIcon className="size-5 fill-current" />
                        </div>
                        <span className="text-base font-semibold">Invory</span>
                    </div>
                    <nav className="flex items-center gap-2">
                        {auth.user ? (
                            <Button asChild variant="outline">
                                <Link href={dashboard()}>Dashboard</Link>
                            </Button>
                        ) : (
                            <>
                                <Button asChild variant="ghost">
                                    <Link href={login()}>Log in</Link>
                                </Button>
                                <Button asChild variant="outline">
                                    <Link href={register()}>Register</Link>
                                </Button>
                            </>
                        )}
                    </nav>
                </header>

                <main className="flex flex-1 items-center justify-center px-4 pb-16 sm:px-6 lg:px-8">
                    <div className="flex w-full max-w-2xl flex-col items-center gap-6 text-center">
                        <h1 className="text-4xl font-semibold tracking-tight sm:text-5xl">
                            Invory
                        </h1>
                        <p className="max-w-prose text-lg text-muted-foreground">
                            Know what every product costs and how many you can
                            make.
                        </p>
                        <div className="flex flex-wrap items-center justify-center gap-3">
                            {auth.user ? (
                                <Button asChild size="lg">
                                    <Link href={dashboard()}>
                                        Go to dashboard
                                    </Link>
                                </Button>
                            ) : (
                                <>
                                    <Button asChild size="lg">
                                        <Link href={login()}>Log in</Link>
                                    </Button>
                                    <Button asChild size="lg" variant="outline">
                                        <Link href={register()}>Register</Link>
                                    </Button>
                                </>
                            )}
                        </div>
                    </div>
                </main>
            </div>
        </>
    );
}

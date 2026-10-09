import type { Auth } from '@/types/auth';
import type { ReferenceData } from '@/types/domain';

declare module 'react' {
    interface InputHTMLAttributes<T> {
        passwordrules?: string;
    }
}

declare module '@inertiajs/core' {
    export interface InertiaConfig {
        sharedPageProps: {
            name: string;
            auth: Auth;
            sidebarOpen: boolean;
            app: { currency: string; locale: string; today: string };
            // Once-prop (ADR-0008): signed-in users only, resent when a reference list changes.
            reference?: ReferenceData;
            [key: string]: unknown;
        };
    }
}

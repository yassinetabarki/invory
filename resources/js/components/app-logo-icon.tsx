import type { SVGAttributes } from 'react';

/** The Invory mark: a bold capital "I". Filled with `currentColor`. */
export default function AppLogoIcon(props: SVGAttributes<SVGElement>) {
    return (
        <svg
            aria-hidden="true"
            focusable="false"
            {...props}
            viewBox="0 0 40 42"
            xmlns="http://www.w3.org/2000/svg"
        >
            <path d="M9 4h22v8h-5.5v18H31v8H9v-8h5.5V12H9V4Z" />
        </svg>
    );
}

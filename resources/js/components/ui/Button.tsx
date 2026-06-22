import { Link } from '@inertiajs/react';
import type { ButtonHTMLAttributes, ReactNode } from 'react';

type Variant = 'primary' | 'secondary' | 'ghost' | 'danger';

const variantClasses: Record<Variant, string> = {
    primary: 'bg-accent text-accent-contrast hover:bg-accent-strong',
    secondary: 'border border-border bg-surface text-ink hover:bg-surface-muted',
    ghost: 'text-ink hover:bg-surface-muted',
    danger: 'bg-danger text-danger-contrast hover:bg-danger-strong',
};

const baseClasses =
    'inline-flex min-h-11 items-center justify-center gap-2 rounded-lg px-4 py-2 text-sm font-semibold transition duration-150 ease-[cubic-bezier(0.25,1,0.5,1)] active:translate-y-px focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-focus disabled:translate-y-0 disabled:cursor-not-allowed disabled:opacity-55';

export type ButtonProps = ButtonHTMLAttributes<HTMLButtonElement> & {
    variant?: Variant;
    loading?: boolean;
    loadingLabel?: string;
};

export function Button({ className = '', variant = 'primary', loading = false, loadingLabel, children, disabled, ...props }: ButtonProps) {
    return (
        <button className={`${baseClasses} ${variantClasses[variant]} ${className}`} disabled={disabled || loading} aria-busy={loading} {...props}>
            {loading ? <span className="size-4 animate-spin rounded-full border-2 border-current border-r-transparent motion-reduce:animate-none" aria-hidden="true" /> : null}
            {loading && loadingLabel ? loadingLabel : children}
        </button>
    );
}

export function ButtonLink({ href, children, variant = 'primary', className = '' }: { href: string; children: ReactNode; variant?: Variant; className?: string }) {
    return (
        <Link href={href} className={`${baseClasses} ${variantClasses[variant]} ${className}`}>
            {children}
        </Link>
    );
}

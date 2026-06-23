import type { ReactNode } from 'react';

export function AuthPanel({ title, description, children, footer }: { title: string; description: string; children: ReactNode; footer?: ReactNode }) {
    return (
        <main id="main-content" className="mx-auto flex min-h-[calc(100vh-4rem)] w-full max-w-6xl items-center px-5 py-10 sm:py-14">
            <section className="mx-auto w-full max-w-md rounded-xl bg-surface p-5 shadow-sm sm:p-7" aria-labelledby="auth-title">
                <div className="space-y-3">
                    <h1 id="auth-title" className="text-2xl font-bold tracking-[-0.02em] text-ink">{title}</h1>
                    <p className="text-sm leading-6 text-muted">{description}</p>
                </div>
                <div className="mt-6">{children}</div>
                {footer ? <div className="mt-6 border-t border-border pt-5 text-sm leading-6 text-muted">{footer}</div> : null}
            </section>
        </main>
    );
}

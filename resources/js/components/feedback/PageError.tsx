import { ButtonLink } from '../ui/Button';

export function PageError({ status, title, description, actionLabel }: { status: number; title: string; description: string; actionLabel: string }) {
    return (
        <main id="main-content" className="mx-auto flex min-h-[70vh] max-w-xl flex-col justify-center px-5 py-16 text-center">
            <p className="text-sm font-semibold text-accent-strong">{status}</p>
            <h1 className="mt-3 text-3xl font-bold tracking-[-0.025em] text-ink sm:text-4xl">{title}</h1>
            <p className="mt-4 text-base leading-7 text-muted">{description}</p>
            <div className="mt-8"><ButtonLink href="/">{actionLabel}</ButtonLink></div>
        </main>
    );
}

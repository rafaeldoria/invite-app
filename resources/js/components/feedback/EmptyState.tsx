import type { ReactNode } from 'react';

export function EmptyState({ title, description, action }: { title: string; description: string; action?: ReactNode }) {
    return (
        <div className="py-8 text-center">
            <div className="mx-auto flex size-10 items-center justify-center rounded-full bg-accent-soft text-accent-strong" aria-hidden="true">+</div>
            <h3 className="mt-4 text-lg font-semibold text-ink">{title}</h3>
            <p className="mx-auto mt-2 max-w-md text-sm leading-6 text-muted">{description}</p>
            {action ? <div className="mt-5">{action}</div> : null}
        </div>
    );
}

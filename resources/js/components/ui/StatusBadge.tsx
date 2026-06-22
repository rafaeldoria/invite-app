import type { ReactNode } from 'react';

type Status = 'confirmed' | 'pending' | 'declined';

const statusClasses: Record<Status, string> = {
    confirmed: 'bg-success-soft text-success-ink',
    pending: 'bg-warning-soft text-warning-ink',
    declined: 'bg-danger-soft text-danger-ink',
};

export function StatusBadge({ status, children }: { status: Status; children: ReactNode }) {
    return (
        <span className={`inline-flex items-center gap-2 rounded-full px-3 py-1 text-sm font-semibold ${statusClasses[status]}`}>
            <span className="size-1.5 rounded-full bg-current" aria-hidden="true" />
            {children}
        </span>
    );
}

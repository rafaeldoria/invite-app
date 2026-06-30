import type { ReactNode } from 'react';

type Tone = 'info' | 'success' | 'error';

const toneClasses: Record<Tone, string> = {
    info: 'bg-info-soft text-info-ink',
    success: 'bg-success-soft text-success-ink',
    error: 'bg-danger-soft text-danger-ink',
};

export function Alert({ title, children, tone = 'info' }: { title: string; children?: ReactNode; tone?: Tone }) {
    return (
        <div className={`rounded-lg p-4 ${toneClasses[tone]}`} role={tone === 'error' ? 'alert' : 'status'}>
            <p className="font-semibold">{title}</p>
            {children ? <div className="mt-1 text-sm leading-6">{children}</div> : null}
        </div>
    );
}

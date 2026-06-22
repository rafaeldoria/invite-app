import { cloneElement, type ReactElement } from 'react';

export function Field({ id, label, required = false, help, error, children }: { id: string; label: string; required?: boolean; help?: string; error?: string; children: ReactElement<{ 'aria-describedby'?: string; 'aria-invalid'?: boolean }> }) {
    const describedBy = [help ? `${id}-help` : null, error ? `${id}-error` : null].filter(Boolean).join(' ') || undefined;
    const control = cloneElement(children, {
        'aria-describedby': describedBy,
        'aria-invalid': error ? true : undefined,
    });

    return (
        <div className="space-y-2">
            <label className="block text-sm font-semibold text-ink" htmlFor={id}>
                {label}
                {required ? <span className="ml-1 text-danger" aria-hidden="true">*</span> : null}
            </label>
            <div>{control}</div>
            {help ? <p id={`${id}-help`} className="text-sm text-muted">{help}</p> : null}
            {error ? <p id={`${id}-error`} className="text-sm font-medium text-danger-ink" role="alert">{error}</p> : null}
        </div>
    );
}

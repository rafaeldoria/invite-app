import type { InputHTMLAttributes, SelectHTMLAttributes, TextareaHTMLAttributes } from 'react';

const controlClasses =
    'min-h-11 w-full rounded-lg border border-border-strong bg-canvas px-3 py-2 text-base text-ink outline-none transition-colors placeholder:text-muted hover:border-muted focus:border-accent focus:ring-2 focus:ring-focus/30 disabled:cursor-not-allowed disabled:bg-surface-muted disabled:text-muted aria-invalid:border-danger aria-invalid:ring-danger/20';

export function TextInput({ className = '', invalid, ...props }: InputHTMLAttributes<HTMLInputElement> & { invalid?: boolean }) {
    return <input className={`${controlClasses} ${className}`} aria-invalid={invalid || undefined} {...props} />;
}

export function Textarea({ className = '', invalid, ...props }: TextareaHTMLAttributes<HTMLTextAreaElement> & { invalid?: boolean }) {
    return <textarea className={`${controlClasses} min-h-28 resize-y ${className}`} aria-invalid={invalid || undefined} {...props} />;
}

export function Select({ className = '', invalid, children, ...props }: SelectHTMLAttributes<HTMLSelectElement> & { invalid?: boolean }) {
    return <select className={`${controlClasses} ${className}`} aria-invalid={invalid || undefined} {...props}>{children}</select>;
}

export function Checkbox({ label, className = '', ...props }: InputHTMLAttributes<HTMLInputElement> & { label: string }) {
    return (
        <label className="inline-flex min-h-11 cursor-pointer items-start gap-3 text-sm text-ink">
            <input type="checkbox" className={`mt-1 size-5 rounded border-border-strong accent-accent focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-focus ${className}`} {...props} />
            <span className="pt-0.5">{label}</span>
        </label>
    );
}

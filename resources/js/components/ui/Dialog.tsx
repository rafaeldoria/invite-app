import { useEffect, useId, useRef, type ReactNode } from 'react';
import { Button } from './Button';

const focusableSelector = 'button:not([disabled]), [href], input:not([disabled]), select:not([disabled]), textarea:not([disabled]), [tabindex]:not([tabindex="-1"])';

export function Dialog({ open, onClose, title, description, cancelLabel, confirmLabel, onConfirm, destructive = false, children }: { open: boolean; onClose: () => void; title: string; description?: string; cancelLabel: string; confirmLabel: string; onConfirm: () => void; destructive?: boolean; children?: ReactNode }) {
    const ref = useRef<HTMLDialogElement>(null);
    const triggerRef = useRef<HTMLElement | null>(null);
    const titleId = useId();
    const descriptionId = useId();

    useEffect(() => {
        const dialog = ref.current;
        if (!dialog) return;

        if (open && !dialog.open) {
            triggerRef.current = document.activeElement as HTMLElement;
            dialog.showModal();
            (dialog.querySelector(focusableSelector) as HTMLElement | null)?.focus();
        } else if (!open && dialog.open) {
            dialog.close();
            triggerRef.current?.focus();
        }
    }, [open]);

    function handleKeyDown(event: React.KeyboardEvent<HTMLDialogElement>) {
        if (event.key !== 'Tab') return;
        const elements = Array.from(ref.current?.querySelectorAll<HTMLElement>(focusableSelector) ?? []);
        if (elements.length === 0) return;
        const first = elements[0];
        const last = elements[elements.length - 1];
        if (event.shiftKey && document.activeElement === first) { event.preventDefault(); last.focus(); }
        if (!event.shiftKey && document.activeElement === last) { event.preventDefault(); first.focus(); }
    }

    return (
        <dialog ref={ref} aria-labelledby={titleId} aria-describedby={description ? descriptionId : undefined} onCancel={(event) => { event.preventDefault(); onClose(); }} onClose={() => triggerRef.current?.focus()} onKeyDown={handleKeyDown} onClick={(event) => event.target === ref.current && onClose()} className="m-auto w-[calc(100%-2rem)] max-w-lg rounded-xl bg-surface p-0 text-ink shadow-lg backdrop:bg-ink/55 backdrop:backdrop-blur-[2px]">
            <div className="p-5 sm:p-6">
                <h2 id={titleId} className="text-xl font-bold tracking-[-0.02em]">{title}</h2>
                {description ? <p id={descriptionId} className="mt-2 text-sm leading-6 text-muted">{description}</p> : null}
                {children}
                <div className="mt-6 flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
                    <Button type="button" variant="secondary" onClick={onClose}>{cancelLabel}</Button>
                    <Button type="button" variant={destructive ? 'danger' : 'primary'} onClick={() => { onConfirm(); onClose(); }}>{confirmLabel}</Button>
                </div>
            </div>
        </dialog>
    );
}

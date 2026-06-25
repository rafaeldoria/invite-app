import { Link, usePage } from '@inertiajs/react';
import { useEffect, useId, useRef, type ReactNode } from 'react';
import type { NavigationItem } from '../../types/shared';

export function NavigationLinks({ items, ariaLabel, onNavigate }: { items: NavigationItem[]; ariaLabel: string; onNavigate?: () => void }) {
    const currentUrl = usePage().url;

    return (
        <nav aria-label={ariaLabel}>
            <ul className="flex flex-col gap-1 md:flex-row">
                {items.map((item) => {
                    const current = currentUrl === item.href || (item.href !== '/' && currentUrl.startsWith(item.href));

                    return (
                        <li key={item.href}>
                            <Link
                                href={item.href}
                                onClick={onNavigate}
                                aria-current={current ? 'page' : undefined}
                                className={`flex min-h-11 items-center rounded-lg px-3 text-sm font-semibold focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-focus ${current ? 'bg-accent-soft text-accent-strong' : 'text-muted hover:bg-surface-muted hover:text-ink'}`}
                            >
                                {item.label}
                            </Link>
                        </li>
                    );
                })}
            </ul>
        </nav>
    );
}

export function MobileNavigation({ open, onClose, items, title, closeLabel, children }: { open: boolean; onClose: () => void; items: NavigationItem[]; title: string; closeLabel: string; children: ReactNode }) {
    const ref = useRef<HTMLDialogElement>(null);
    const triggerRef = useRef<HTMLElement | null>(null);
    const titleId = useId();

    useEffect(() => {
        const dialog = ref.current;
        if (!dialog) return;

        if (open && !dialog.open) {
            triggerRef.current = document.activeElement as HTMLElement;
            dialog.showModal();
            dialog.querySelector<HTMLElement>('button')?.focus();
        }

        if (!open && dialog.open) {
            dialog.close();
            triggerRef.current?.focus();
        }
    }, [open]);

    return (
        <dialog
            ref={ref}
            aria-labelledby={titleId}
            onCancel={(event) => {
                event.preventDefault();
                onClose();
            }}
            onClose={() => triggerRef.current?.focus()}
            onClick={(event) => event.target === ref.current && onClose()}
            className="ml-auto mr-0 h-full max-h-none w-[min(22rem,calc(100%-2rem))] bg-surface p-0 text-ink shadow-lg backdrop:bg-ink/55"
        >
            <div className="flex min-h-full flex-col p-5">
                <div className="flex items-center justify-between gap-4">
                    <p id={titleId} className="font-bold">{title}</p>
                    <button
                        type="button"
                        onClick={onClose}
                        className="flex size-11 items-center justify-center rounded-lg text-2xl hover:bg-surface-muted focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-focus"
                        aria-label={closeLabel}
                    >
                        ×
                    </button>
                </div>
                <div className="mt-6">
                    <NavigationLinks items={items} ariaLabel={title} onNavigate={onClose} />
                </div>
                <div className="mt-auto border-t border-border pt-5">
                    {children}
                </div>
            </div>
        </dialog>
    );
}

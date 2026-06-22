import { useEffect, useState } from 'react';

export function FlashNotice({ success, error, dismissLabel }: { success: string | null; error: string | null; dismissLabel: string }) {
    const [visible, setVisible] = useState(true);
    const message = error ?? success;

    useEffect(() => {
        setVisible(true);
    }, [message]);

    if (!message || !visible) return null;

    return (
        <div className="fixed inset-x-4 top-4 z-toast mx-auto flex max-w-xl items-start justify-between gap-4 rounded-lg bg-ink px-4 py-3 text-canvas shadow-md" role={error ? 'alert' : 'status'} aria-live={error ? 'assertive' : 'polite'}>
            <p className="text-sm font-medium leading-6">{message}</p>
            <button type="button" onClick={() => setVisible(false)} className="-m-1 min-h-11 min-w-11 rounded-md p-2 text-current focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-focus" aria-label={dismissLabel}>×</button>
        </div>
    );
}

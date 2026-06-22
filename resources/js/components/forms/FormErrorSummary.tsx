import { useEffect, useId, useRef } from 'react';

export function FormErrorSummary({ title, errors }: { title: string; errors: Array<{ fieldId: string; message: string }> }) {
    const ref = useRef<HTMLDivElement>(null);
    const previousErrorCount = useRef(0);
    const titleId = useId();

    useEffect(() => {
        if (previousErrorCount.current === 0 && errors.length > 0) {
            ref.current?.focus();
        }

        previousErrorCount.current = errors.length;
    }, [errors.length]);

    if (errors.length === 0) return null;

    return (
        <div ref={ref} tabIndex={-1} className="rounded-lg bg-danger-soft p-4 text-danger-ink outline-none focus-visible:ring-2 focus-visible:ring-focus" role="alert" aria-labelledby={titleId}>
            <p id={titleId} className="font-semibold">{title}</p>
            <ul className="mt-2 list-disc space-y-1 pl-5 text-sm">
                {errors.map((error) => <li key={error.fieldId}><a className="underline underline-offset-2" href={`#${error.fieldId}`}>{error.message}</a></li>)}
            </ul>
        </div>
    );
}

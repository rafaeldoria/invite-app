export function LoadingIndicator({ label }: { label: string }) {
    return (
        <div className="inline-flex items-center gap-3 text-sm text-muted" role="status">
            <span className="size-5 animate-spin rounded-full border-2 border-border-strong border-r-accent motion-reduce:animate-none" aria-hidden="true" />
            <span>{label}</span>
        </div>
    );
}

export function Skeleton({ className = '' }: { className?: string }) {
    return <span className={`block animate-pulse rounded-md bg-surface-muted motion-reduce:animate-none ${className}`} aria-hidden="true" />;
}

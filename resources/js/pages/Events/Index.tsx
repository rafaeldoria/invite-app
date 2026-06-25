import { Head, Link } from '@inertiajs/react';
import { AuthenticatedLayout } from '../../layouts/AuthenticatedLayout';
import { EmptyState } from '../../components/feedback/EmptyState';
import { ButtonLink } from '../../components/ui/Button';
import { Card } from '../../components/ui/Card';
import { useLocale } from '../../hooks/use-locale';
import { formatDate, formatTime } from '../../utils/formatting';
import type { PaginatedEvents } from '../../types/events';

export default function Index({ events, links }: { events: PaginatedEvents; links: { create: string } }) {
    const { locale, t } = useLocale();
    const hasEvents = events.data.length > 0;

    return (
        <AuthenticatedLayout>
            <Head title={t('events.index.title')} />
            <main id="main-content" className="mx-auto w-full max-w-7xl px-5 py-8 sm:py-10">
                <div className="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                    <div>
                        <h1 className="text-2xl font-bold tracking-[-0.02em] text-ink">{t('events.index.title')}</h1>
                        <p className="mt-2 max-w-2xl text-sm leading-6 text-muted">{t('events.index.description')}</p>
                    </div>
                    <ButtonLink href={links.create} className="sm:shrink-0">{t('events.index.create')}</ButtonLink>
                </div>

                <section className="mt-8" aria-label={t('events.index.listLabel')}>
                    {!hasEvents ? (
                        <Card>
                            <EmptyState title={t('events.index.emptyTitle')} description={t('events.index.emptyDescription')} action={<ButtonLink href={links.create}>{t('events.index.createFirst')}</ButtonLink>} />
                        </Card>
                    ) : (
                        <>
                            <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                                {events.data.map((event) => (
                                    <article key={event.public_id} className="overflow-hidden rounded-xl bg-surface shadow-sm">
                                        <Link href={event.links.show ?? '#'} className="block focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-focus">
                                            {event.cover_image?.url ? (
                                                <img src={event.cover_image.url} alt={t('events.coverAlt', { name: event.name })} className="aspect-[16/9] w-full bg-canvas object-cover" />
                                            ) : (
                                                <div className="flex aspect-[16/9] items-center justify-center bg-accent-soft px-5 text-center text-sm font-semibold text-accent-strong">
                                                    {t('events.index.noCover')}
                                                </div>
                                            )}
                                        </Link>
                                        <div className="space-y-4 p-5">
                                            <div>
                                                <h2 className="text-lg font-semibold text-ink">
                                                    <Link href={event.links.show ?? '#'} className="rounded-md focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-focus">
                                                        {event.name}
                                                    </Link>
                                                </h2>
                                                {event.theme ? <p className="mt-1 text-sm text-muted">{event.theme}</p> : null}
                                            </div>
                                            <dl className="grid gap-3 text-sm">
                                                <div>
                                                    <dt className="font-semibold text-ink">{t('events.fields.startsAt')}</dt>
                                                    <dd className="mt-1 text-muted">{formatDate(event.starts_at, locale, event.timezone)} · {formatTime(event.starts_at, locale, event.timezone)}</dd>
                                                </div>
                                                <div>
                                                    <dt className="font-semibold text-ink">{t('events.fields.location')}</dt>
                                                    <dd className="mt-1 text-muted">{event.location}</dd>
                                                </div>
                                            </dl>
                                            <div className="flex flex-wrap gap-2">
                                                <ButtonLink href={event.links.show ?? '#'} variant="secondary">{t('events.index.view')}</ButtonLink>
                                                <ButtonLink href={event.links.edit ?? '#'} variant="ghost">{t('events.index.edit')}</ButtonLink>
                                            </div>
                                        </div>
                                    </article>
                                ))}
                            </div>

                            {events.last_page > 1 ? (
                                <nav className="mt-6 flex flex-col gap-3 border-t border-border pt-5 text-sm text-muted sm:flex-row sm:items-center sm:justify-between" aria-label={t('events.index.paginationLabel')}>
                                    <p>
                                        {t('events.index.paginationSummary', {
                                            from: events.from ?? 0,
                                            to: events.to ?? 0,
                                            total: events.total,
                                        })}
                                    </p>
                                    <div className="flex items-center gap-2">
                                        {events.prev_page_url ? (
                                            <ButtonLink href={events.prev_page_url} variant="secondary">{t('events.index.previous')}</ButtonLink>
                                        ) : (
                                            <span className="inline-flex min-h-11 items-center justify-center rounded-lg border border-border bg-surface px-4 py-2 text-sm font-semibold text-muted opacity-55" aria-disabled="true">
                                                {t('events.index.previous')}
                                            </span>
                                        )}
                                        <span className="px-2 font-medium text-ink">
                                            {t('events.index.pageStatus', { page: events.current_page, pages: events.last_page })}
                                        </span>
                                        {events.next_page_url ? (
                                            <ButtonLink href={events.next_page_url} variant="secondary">{t('events.index.next')}</ButtonLink>
                                        ) : (
                                            <span className="inline-flex min-h-11 items-center justify-center rounded-lg border border-border bg-surface px-4 py-2 text-sm font-semibold text-muted opacity-55" aria-disabled="true">
                                                {t('events.index.next')}
                                            </span>
                                        )}
                                    </div>
                                </nav>
                            ) : null}
                        </>
                    )}
                </section>
            </main>
        </AuthenticatedLayout>
    );
}

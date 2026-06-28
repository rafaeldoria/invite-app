import { Head } from '@inertiajs/react';
import { useState } from 'react';
import { Alert } from '../../components/feedback/Alert';
import { ButtonLink } from '../../components/ui/Button';
import { PublicLayout } from '../../layouts/PublicLayout';
import { useLocale } from '../../hooks/use-locale';
import type { PublicEventDetail, PublicEventMeta } from '../../types/events';
import { formatDate, formatTime } from '../../utils/formatting';

export default function Show({ event, meta }: { event: PublicEventDetail; meta: PublicEventMeta }) {
    const { locale, t } = useLocale();
    const [failedCoverImageUrl, setFailedCoverImageUrl] = useState<string | null>(null);
    const coverImageUrl = event.cover_image?.url ?? null;
    const showCoverImage = coverImageUrl !== null && failedCoverImageUrl !== coverImageUrl;

    return (
        <PublicLayout>
            <Head title={meta.title}>
                <meta name="description" content={meta.description} />
                <meta property="og:title" content={meta.title} />
                <meta property="og:description" content={meta.description} />
                <meta property="og:url" content={meta.url} />
                {meta.image ? <meta property="og:image" content={meta.image} /> : null}
            </Head>

            <main id="main-content" className="mx-auto grid w-full max-w-6xl gap-6 px-5 py-6 lg:grid-cols-[minmax(0,1fr)_21rem] lg:items-start lg:py-10">
                <article className="min-w-0 overflow-hidden rounded-xl bg-surface shadow-sm">
                    {showCoverImage ? (
                        <img src={coverImageUrl} alt={t('events.coverAlt', { name: event.name })} className="aspect-[16/10] w-full bg-canvas object-cover sm:aspect-[16/8]" onError={() => setFailedCoverImageUrl(coverImageUrl)} />
                    ) : (
                        <div className="flex aspect-[16/10] w-full items-center justify-center bg-accent-soft px-5 text-center text-sm font-semibold text-accent-strong sm:aspect-[16/8]">
                            {t('publicEvent.noCover')}
                        </div>
                    )}

                    <div className="space-y-6 p-5 sm:p-7">
                        <div className="space-y-3">
                            {event.theme ? <p className="text-sm font-semibold text-accent-strong">{event.theme}</p> : null}
                            <h1 className="text-3xl font-bold tracking-[-0.02em] text-ink sm:text-4xl">{event.name}</h1>
                        </div>

                        <dl className="grid gap-3 text-sm sm:grid-cols-2">
                            <div className="rounded-lg bg-canvas p-4">
                                <dt className="font-semibold text-ink">{t('events.fields.startsAt')}</dt>
                                <dd className="mt-1 text-muted">{formatDate(event.starts_at, locale, event.timezone)} · {formatTime(event.starts_at, locale, event.timezone)}</dd>
                                <dd className="mt-1 text-xs font-medium text-muted">{event.timezone}</dd>
                            </div>
                            <div className="rounded-lg bg-canvas p-4">
                                <dt className="font-semibold text-ink">{t('events.fields.location')}</dt>
                                <dd className="mt-1 break-words text-muted">{event.location}</dd>
                            </div>
                        </dl>

                        <section aria-labelledby="public-event-description-title">
                            <h2 id="public-event-description-title" className="text-base font-semibold text-ink">{t('events.show.description')}</h2>
                            <p className="mt-2 max-w-3xl whitespace-pre-line text-sm leading-6 text-muted">{event.description}</p>
                        </section>
                    </div>
                </article>

                <aside className="min-w-0 space-y-4 lg:sticky lg:top-6">
                    <section className="rounded-xl bg-surface p-5 shadow-sm" aria-labelledby="public-event-rsvp-title">
                        <h2 id="public-event-rsvp-title" className="text-lg font-semibold text-ink">{t('publicEvent.rsvpTitle')}</h2>
                        <p className="mt-2 text-sm leading-6 text-muted">{t('publicEvent.rsvpDescription')}</p>
                        {event.rsvp.available && event.rsvp.url ? (
                            <ButtonLink href={event.rsvp.url} className="mt-4 w-full">
                                {t('publicEvent.rsvpAction')}
                            </ButtonLink>
                        ) : (
                            <div className="mt-4">
                                <Alert title={t('publicEvent.rsvpPendingTitle')} tone="info">{t('publicEvent.rsvpPendingDescription')}</Alert>
                            </div>
                        )}
                    </section>

                    <section className="rounded-xl bg-surface p-5 shadow-sm" aria-labelledby="public-event-share-title">
                        <h2 id="public-event-share-title" className="text-base font-semibold text-ink">{t('publicEvent.linkTitle')}</h2>
                        <p className="mt-2 break-all text-sm leading-6 text-muted">{event.canonical_url}</p>
                    </section>
                </aside>
            </main>
        </PublicLayout>
    );
}

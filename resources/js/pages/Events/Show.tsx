import { Head, Link, useForm } from '@inertiajs/react';
import { useState } from 'react';
import { Alert } from '../../components/feedback/Alert';
import { Button, ButtonLink } from '../../components/ui/Button';
import { Card } from '../../components/ui/Card';
import { Dialog } from '../../components/ui/Dialog';
import { AuthenticatedLayout } from '../../layouts/AuthenticatedLayout';
import { useLocale } from '../../hooks/use-locale';
import type { EventDetail } from '../../types/events';
import { formatDate, formatTime } from '../../utils/formatting';

export default function Show({ event }: { event: EventDetail }) {
    const { locale, t } = useLocale();
    const [deleteOpen, setDeleteOpen] = useState(false);
    const deleteForm = useForm({});

    function deleteEvent() {
        if (!event.links.destroy) return;

        deleteForm.delete(event.links.destroy, {
            preserveScroll: true,
        });
    }

    return (
        <AuthenticatedLayout>
            <Head title={event.name} />
            <main id="main-content" className="mx-auto w-full max-w-7xl px-5 py-8 sm:py-10">
                <div className="mb-6">
                    <Link href={event.links.index ?? '/events'} className="rounded-md text-sm font-semibold text-accent-strong underline underline-offset-4 focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-focus">
                        {t('events.show.back')}
                    </Link>
                </div>

                <div className="grid gap-6 lg:grid-cols-[minmax(0,1fr)_20rem] lg:items-start">
                    <article className="overflow-hidden rounded-xl bg-surface shadow-sm">
                        {event.cover_image?.url ? (
                            <img src={event.cover_image.url} alt={t('events.coverAlt', { name: event.name })} className="aspect-[16/9] w-full bg-canvas object-cover" />
                        ) : (
                            <div className="flex aspect-[16/9] items-center justify-center bg-accent-soft px-5 text-center text-sm font-semibold text-accent-strong">
                                {t('events.show.noCover')}
                            </div>
                        )}
                        <div className="space-y-6 p-5 sm:p-6">
                            <div>
                                <h1 className="text-2xl font-bold tracking-[-0.02em] text-ink">{event.name}</h1>
                                {event.theme ? <p className="mt-2 text-sm font-medium text-muted">{event.theme}</p> : null}
                            </div>

                            <dl className="grid gap-4 text-sm sm:grid-cols-2">
                                <div className="rounded-lg bg-canvas p-4">
                                    <dt className="font-semibold text-ink">{t('events.fields.startsAt')}</dt>
                                    <dd className="mt-1 text-muted">{formatDate(event.starts_at, locale, event.timezone)} · {formatTime(event.starts_at, locale, event.timezone)}</dd>
                                    <dd className="mt-1 text-xs text-muted">{event.timezone}</dd>
                                </div>
                                <div className="rounded-lg bg-canvas p-4">
                                    <dt className="font-semibold text-ink">{t('events.fields.location')}</dt>
                                    <dd className="mt-1 text-muted">{event.location}</dd>
                                </div>
                            </dl>

                            <section aria-labelledby="event-description-title">
                                <h2 id="event-description-title" className="text-base font-semibold text-ink">{t('events.show.description')}</h2>
                                <p className="mt-2 max-w-3xl whitespace-pre-line text-sm leading-6 text-muted">{event.description}</p>
                            </section>
                        </div>
                    </article>

                    <aside className="space-y-4">
                        <Card>
                            <h2 className="text-base font-semibold text-ink">{t('events.show.actions')}</h2>
                            <div className="mt-4 grid gap-3">
                                <ButtonLink href={event.links.edit ?? '#'}>{t('events.show.edit')}</ButtonLink>
                                <Button type="button" variant="danger" onClick={() => setDeleteOpen(true)}>{t('events.show.delete')}</Button>
                            </div>
                        </Card>

                        <Card>
                            <h2 className="text-base font-semibold text-ink">{t('events.show.nextTitle')}</h2>
                            <p className="mt-2 text-sm leading-6 text-muted">{t('events.show.nextDescription')}</p>
                        </Card>
                    </aside>
                </div>
            </main>

            <Dialog
                open={deleteOpen}
                onClose={() => setDeleteOpen(false)}
                title={t('events.delete.title')}
                description={t('events.delete.description', { name: event.name })}
                cancelLabel={t('events.delete.cancel')}
                confirmLabel={deleteForm.processing ? t('events.delete.deleting') : t('events.delete.confirm')}
                onConfirm={deleteEvent}
                destructive
                closeOnConfirm={false}
                confirmDisabled={deleteForm.processing}
            >
                {deleteForm.hasErrors ? (
                    <div className="mt-4">
                        <Alert title={t('events.delete.errorTitle')} tone="error">{t('events.delete.errorDescription')}</Alert>
                    </div>
                ) : null}
            </Dialog>
        </AuthenticatedLayout>
    );
}

import { Head, Link } from '@inertiajs/react';
import { useMemo, useState } from 'react';
import { EmptyState } from '../../components/feedback/EmptyState';
import { Button, ButtonLink } from '../../components/ui/Button';
import { Card } from '../../components/ui/Card';
import { Dialog } from '../../components/ui/Dialog';
import { AuthenticatedLayout } from '../../layouts/AuthenticatedLayout';
import { useLocale } from '../../hooks/use-locale';
import type { TranslationKey } from '../../locales';
import type { GuestStatus } from '../../types/guests';

type DashboardMetrics = {
    total: number;
    confirmed: number;
    declined: number;
    pending: number;
    expected_attendees?: number;
};

type DashboardMetricKey = keyof Pick<DashboardMetrics, 'total' | GuestStatus>;
type FullGuestListSort = 'guest' | 'alphabetical' | 'child';

type FullGuestListItem = {
    name: string;
    primary_guest: string;
    is_child: boolean;
    is_primary: boolean;
};

type Props = {
    event: {
        name: string;
        links: {
            show: string;
            guests: string;
        };
    };
    metrics: DashboardMetrics;
    fullGuestList: FullGuestListItem[];
    links: {
        guests: Record<'all' | GuestStatus, string>;
    };
};

const cardOrder: DashboardMetricKey[] = ['total', 'confirmed', 'declined', 'pending'];

const toneClasses: Record<DashboardMetricKey, string> = {
    total: 'bg-info-ink',
    confirmed: 'bg-success-ink',
    declined: 'bg-danger-ink',
    pending: 'bg-warning-ink',
};

const sortOptions: FullGuestListSort[] = ['guest', 'alphabetical', 'child'];

export default function Dashboard({ event, metrics, fullGuestList, links }: Props) {
    const { locale, t } = useLocale();
    const [fullListOpen, setFullListOpen] = useState(false);
    const [fullListSort, setFullListSort] = useState<FullGuestListSort>('guest');
    const formatter = new Intl.NumberFormat(locale);
    const hasGuests = metrics.total > 0;
    const sortedFullGuestList = useMemo(
        () => sortFullGuestList(fullGuestList, fullListSort),
        [fullGuestList, fullListSort],
    );

    function cardHref(key: DashboardMetricKey): string {
        return key === 'total' ? links.guests.all : links.guests[key];
    }

    return (
        <AuthenticatedLayout>
            <Head title={t('dashboard.title')} />
            <main id="main-content" className="mx-auto w-full max-w-7xl px-5 py-8 sm:py-10">
                <div className="mb-6">
                    <Link href={event.links.show} className="rounded-md text-sm font-semibold text-accent-strong underline underline-offset-4 focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-focus">
                        {t('dashboard.back')}
                    </Link>
                </div>

                <div className="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                    <div>
                        <p className="text-sm font-semibold text-muted">{event.name}</p>
                        <h1 className="mt-1 text-2xl font-bold tracking-[-0.02em] text-ink">{t('dashboard.title')}</h1>
                        <p className="mt-2 max-w-2xl text-sm leading-6 text-muted">{t('dashboard.description')}</p>
                    </div>
                    <ButtonLink href={event.links.guests} variant="secondary" className="sm:shrink-0">
                        {t('dashboard.manageGuests')}
                    </ButtonLink>
                </div>

                <section className="mt-8" aria-labelledby="dashboard-metrics-title">
                    <div className="flex flex-col gap-3 border-b border-border pb-5 sm:flex-row sm:items-end sm:justify-between">
                        <div>
                            <h2 id="dashboard-metrics-title" className="text-lg font-semibold text-ink">{t('dashboard.metricsTitle')}</h2>
                            <p className="mt-1 text-sm leading-6 text-muted">
                                {hasGuests ? t('dashboard.metricsSummary') : t('dashboard.emptySummary')}
                            </p>
                        </div>
                        {metrics.expected_attendees !== undefined ? (
                            <div className="flex flex-col gap-3 sm:items-end">
                                <div className="rounded-lg bg-surface-muted px-4 py-3 text-sm">
                                    <span className="font-semibold text-ink">{formatter.format(metrics.expected_attendees)}</span>
                                    {' '}
                                    <span className="ml-2 text-muted">{t('dashboard.expectedAttendees')}</span>
                                </div>
                                <Button type="button" variant="secondary" onClick={() => setFullListOpen(true)} disabled={fullGuestList.length === 0}>
                                    {t('dashboard.fullList.action')}
                                </Button>
                            </div>
                        ) : null}
                    </div>

                    <div className="mt-6 grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                        {cardOrder.map((key) => {
                            const label = t(`dashboard.metrics.${key}.label` as TranslationKey);
                            const value = formatter.format(metrics[key]);

                            return (
                                <Link
                                    key={key}
                                    href={cardHref(key)}
                                    className="group block min-h-40 rounded-xl bg-surface p-5 shadow-sm transition duration-150 ease-[cubic-bezier(0.25,1,0.5,1)] hover:-translate-y-0.5 hover:bg-surface-muted focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-focus motion-reduce:hover:translate-y-0"
                                    aria-label={t('dashboard.metrics.cardLabel', { label, count: value })}
                                >
                                    <div className="flex h-full flex-col justify-between gap-6">
                                        <div className="flex items-start justify-between gap-3">
                                            <div>
                                                <p className="text-sm font-semibold text-ink">{label}</p>
                                                <p className="mt-2 text-sm leading-6 text-muted">{t(`dashboard.metrics.${key}.description` as TranslationKey)}</p>
                                            </div>
                                            <span className={`mt-1 inline-flex size-3 shrink-0 rounded-full ${toneClasses[key]}`} aria-hidden="true" />
                                        </div>
                                        <p className="break-words text-4xl font-bold tracking-[-0.02em] text-ink">{value}</p>
                                    </div>
                                </Link>
                            );
                        })}
                    </div>
                </section>

                {!hasGuests ? (
                    <Card className="mt-8">
                        <EmptyState
                            title={t('dashboard.emptyTitle')}
                            description={t('dashboard.emptyDescription')}
                            action={<ButtonLink href={event.links.guests}>{t('dashboard.emptyAction')}</ButtonLink>}
                        />
                    </Card>
                ) : null}
            </main>

            <Dialog
                open={fullListOpen}
                onClose={() => setFullListOpen(false)}
                title={t('dashboard.fullList.title')}
                description={t('dashboard.fullList.description')}
                cancelLabel={t('dashboard.fullList.close')}
            >
                <div className="mt-5 space-y-4">
                    <div>
                        <p className="mb-2 text-sm font-semibold text-ink">{t('dashboard.fullList.sortLabel')}</p>
                        <div className="flex flex-wrap gap-2">
                            {sortOptions.map((option) => (
                                <button
                                    key={option}
                                    type="button"
                                    onClick={() => setFullListSort(option)}
                                    className={`inline-flex min-h-11 items-center rounded-lg px-4 py-2 text-sm font-semibold transition-colors focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-focus ${fullListSort === option ? 'bg-accent text-accent-contrast' : 'border border-border bg-surface text-ink hover:bg-surface-muted'}`}
                                    aria-pressed={fullListSort === option}
                                >
                                    {t(`dashboard.fullList.sort.${option}` as TranslationKey)}
                                </button>
                            ))}
                        </div>
                    </div>

                    {sortedFullGuestList.length > 0 ? (
                        <ul className="max-h-[55vh] divide-y divide-border overflow-y-auto rounded-lg border border-border" aria-label={t('dashboard.fullList.listLabel')}>
                            {sortedFullGuestList.map((item, index) => (
                                <li key={`${item.primary_guest}-${item.name}-${index}`} className="grid gap-2 px-4 py-3 sm:grid-cols-[1fr_auto] sm:items-center">
                                    <div className="min-w-0">
                                        <p className="break-words text-sm font-semibold text-ink">{item.name}</p>
                                        {!item.is_primary ? (
                                            <p className="mt-1 break-words text-xs text-muted">{t('dashboard.fullList.primaryGuest', { name: item.primary_guest })}</p>
                                        ) : null}
                                    </div>
                                    <span className="w-fit rounded-full bg-surface-muted px-3 py-1 text-xs font-semibold text-muted">
                                        {item.is_child ? t('dashboard.fullList.child') : t('dashboard.fullList.adult')}
                                    </span>
                                </li>
                            ))}
                        </ul>
                    ) : (
                        <p className="rounded-lg border border-border bg-surface-muted px-4 py-3 text-sm leading-6 text-muted">
                            {t('dashboard.fullList.empty')}
                        </p>
                    )}
                </div>
            </Dialog>
        </AuthenticatedLayout>
    );
}

function sortFullGuestList(items: FullGuestListItem[], sort: FullGuestListSort): FullGuestListItem[] {
    const sorted = [...items];

    return sorted.sort((a, b) => {
        if (sort === 'child' && a.is_child !== b.is_child) {
            return a.is_child ? -1 : 1;
        }

        if (sort === 'guest') {
            const guestCompare = compareText(a.primary_guest, b.primary_guest);
            if (guestCompare !== 0) return guestCompare;
            if (a.is_primary !== b.is_primary) return a.is_primary ? -1 : 1;
        }

        return compareText(a.name, b.name);
    });
}

function compareText(a: string, b: string): number {
    return a.localeCompare(b, undefined, { sensitivity: 'base' });
}

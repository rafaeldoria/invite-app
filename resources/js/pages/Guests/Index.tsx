import { Head, Link, useForm } from '@inertiajs/react';
import { useMemo, useState } from 'react';
import { Alert } from '../../components/feedback/Alert';
import { EmptyState } from '../../components/feedback/EmptyState';
import { Field } from '../../components/forms/Field';
import { Select, TextInput } from '../../components/forms/controls';
import { FormErrorSummary } from '../../components/forms/FormErrorSummary';
import { Button, ButtonLink } from '../../components/ui/Button';
import { Card } from '../../components/ui/Card';
import { Dialog } from '../../components/ui/Dialog';
import { StatusBadge } from '../../components/ui/StatusBadge';
import { AuthenticatedLayout } from '../../layouts/AuthenticatedLayout';
import { useLocale } from '../../hooks/use-locale';
import type { FullGuestListItem, GuestFormData, GuestListItem, GuestStatus, GuestStatusOption, PaginatedGuests } from '../../types/guests';
import type { TranslationKey } from '../../locales';

type Feedback = { tone: 'success' | 'error'; message: string } | null;
type FullGuestListSort = 'guest' | 'alphabetical' | 'child';
type GuestListView = 'full';

type Props = {
    event: {
        name: string;
        links: {
            show: string;
            guests: string;
        };
    };
    guests: PaginatedGuests;
    fullGuestList: FullGuestListItem[];
    filters: {
        status: GuestStatus | null;
        view: GuestListView | null;
    };
    statusOptions: GuestStatusOption[];
    links: {
        store: string;
    };
};

const defaultGuestForm: GuestFormData = {
    name: '',
    status: 'pending',
    adult_companions: 0,
    child_companions: 0,
};

const fullListSortOptions: FullGuestListSort[] = ['guest', 'alphabetical', 'child'];

export default function Index({ event, guests, fullGuestList, filters, statusOptions, links }: Props) {
    const { t, tp } = useLocale();
    const [createOpen, setCreateOpen] = useState(false);
    const [editingGuest, setEditingGuest] = useState<GuestListItem | null>(null);
    const [deletingGuest, setDeletingGuest] = useState<GuestListItem | null>(null);
    const [companionGuest, setCompanionGuest] = useState<GuestListItem | null>(null);
    const [fullListSort, setFullListSort] = useState<FullGuestListSort>('guest');
    const [feedback, setFeedback] = useState<Feedback>(null);

    const createForm = useForm<GuestFormData>(defaultGuestForm);
    const editForm = useForm<GuestFormData>(defaultGuestForm);
    const deleteForm = useForm({});
    const createErrors = [
        createForm.errors.name ? { fieldId: 'guest-create-name', message: createForm.errors.name } : null,
    ].filter((error): error is { fieldId: string; message: string } => error !== null);
    const editErrors = [
        editForm.errors.name ? { fieldId: 'guest-edit-name', message: editForm.errors.name } : null,
        editForm.errors.status ? { fieldId: 'guest-edit-status', message: editForm.errors.status } : null,
        editForm.errors.adult_companions ? { fieldId: 'guest-edit-adults', message: editForm.errors.adult_companions } : null,
        editForm.errors.child_companions ? { fieldId: 'guest-edit-children', message: editForm.errors.child_companions } : null,
    ].filter((error): error is { fieldId: string; message: string } => error !== null);

    const selectedFilter = filters.status ?? 'all';
    const isFullList = filters.view === 'full';
    const hasGuests = guests.data.length > 0;
    const isFiltered = filters.status !== null && !isFullList;
    const sortedFullGuestList = useMemo(
        () => sortFullGuestList(fullGuestList, fullListSort),
        [fullGuestList, fullListSort],
    );

    function openCreateDialog() {
        createForm.clearErrors();
        createForm.setData(defaultGuestForm);
        setCreateOpen(true);
    }

    function createGuest() {
        createForm.post(links.store, {
            preserveScroll: true,
            onSuccess: () => {
                setCreateOpen(false);
                createForm.reset();
                setFeedback({ tone: 'success', message: t('guests.feedback.created') });
            },
            onError: () => setCreateOpen(true),
        });
    }

    function openEditDialog(guest: GuestListItem) {
        editForm.clearErrors();
        editForm.setData({
            name: guest.name,
            status: guest.status,
            adult_companions: guest.adult_companions,
            child_companions: guest.child_companions,
        });
        setEditingGuest(guest);
    }

    function updateEditStatus(status: GuestStatus) {
        editForm.setData((data) => ({
            ...data,
            status,
            adult_companions: status === 'confirmed' ? data.adult_companions : 0,
            child_companions: status === 'confirmed' ? data.child_companions : 0,
        }));
    }

    function updateGuest() {
        if (!editingGuest) return;

        editForm.patch(editingGuest.links.update, {
            preserveScroll: true,
            onSuccess: () => {
                setEditingGuest(null);
                setFeedback({ tone: 'success', message: t('guests.feedback.updated') });
            },
            onError: () => setEditingGuest(editingGuest),
        });
    }

    function deleteGuest() {
        if (!deletingGuest) return;

        deleteForm.delete(deletingGuest.links.destroy, {
            preserveScroll: true,
            onSuccess: () => {
                setDeletingGuest(null);
                setFeedback({ tone: 'success', message: t('guests.feedback.deleted') });
            },
            onError: () => setFeedback({ tone: 'error', message: t('guests.feedback.deleteError') }),
        });
    }

    async function copyInvitation(guest: GuestListItem) {
        if (!navigator.clipboard) {
            setFeedback({ tone: 'error', message: t('guests.feedback.copyError') });
            return;
        }

        try {
            await navigator.clipboard.writeText(guest.invitation_url);
            setFeedback({ tone: 'success', message: t('guests.feedback.copySuccess', { name: guest.name }) });
        } catch {
            setFeedback({ tone: 'error', message: t('guests.feedback.copyError') });
        }
    }

    function filterHref(status: GuestStatus | 'all') {
        return status === 'all' ? event.links.guests : `${event.links.guests}?status=${status}`;
    }

    function fullListHref() {
        return `${event.links.guests}?view=full`;
    }

    return (
        <AuthenticatedLayout>
            <Head title={t('guests.index.title')} />
            <main id="main-content" className="mx-auto w-full max-w-7xl px-5 py-8 sm:py-10">
                <div className="mb-6">
                    <Link href={event.links.show} className="rounded-md text-sm font-semibold text-accent-strong underline underline-offset-4 focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-focus">
                        {t('guests.index.back')}
                    </Link>
                </div>

                <div className="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                    <div>
                        <p className="text-sm font-semibold text-muted">{event.name}</p>
                        <h1 className="mt-1 text-2xl font-bold tracking-[-0.02em] text-ink">{t('guests.index.title')}</h1>
                        <p className="mt-2 max-w-2xl text-sm leading-6 text-muted">{t('guests.index.description')}</p>
                    </div>
                    <Button type="button" onClick={openCreateDialog} className="sm:shrink-0">{t('guests.index.add')}</Button>
                </div>

                {feedback ? (
                    <div className="mt-6">
                        <Alert title={feedback.message} tone={feedback.tone}>
                            {feedback.tone === 'success' ? t('guests.feedback.successHint') : t('guests.feedback.errorHint')}
                        </Alert>
                    </div>
                ) : null}

                <section className="mt-8" aria-labelledby="guest-list-title">
                    <div className="flex flex-col gap-4 border-b border-border pb-5 lg:flex-row lg:items-end lg:justify-between">
                        <div>
                            <h2 id="guest-list-title" className="text-lg font-semibold text-ink">{t('guests.index.listTitle')}</h2>
                            <p className="mt-1 text-sm text-muted">
                                {isFullList
                                    ? guests.total > 0
                                        ? t('guests.fullList.pageSummary', { from: guests.from ?? 0, to: guests.to ?? 0, total: guests.total })
                                        : t('guests.index.noGuestsSummary')
                                    : guests.total > 0
                                    ? t('guests.index.listSummary', { from: guests.from ?? 0, to: guests.to ?? 0, total: guests.total })
                                    : t('guests.index.noGuestsSummary')}
                            </p>
                        </div>
                        <nav className="flex flex-wrap gap-2" aria-label={t('guests.index.filterLabel')}>
                            <FilterLink href={filterHref('all')} active={!isFullList && selectedFilter === 'all'} label={t('guests.filter.all')} />
                            {statusOptions.map((option) => (
                                <FilterLink key={option.value} href={filterHref(option.value)} active={!isFullList && selectedFilter === option.value} label={t(option.label_key as TranslationKey)} />
                            ))}
                            <FilterLink href={fullListHref()} active={isFullList} label={t('dashboard.fullList.action')} />
                        </nav>
                    </div>

                    {isFullList ? (
                        <FullGuestListView
                            items={sortedFullGuestList}
                            pagination={guests}
                            sort={fullListSort}
                            onSortChange={setFullListSort}
                            t={t}
                        />
                    ) : !hasGuests ? (
                        <Card className="mt-6">
                            <EmptyState
                                title={isFiltered ? t('guests.index.filterEmptyTitle') : t('guests.index.emptyTitle')}
                                description={isFiltered ? t('guests.index.filterEmptyDescription') : t('guests.index.emptyDescription')}
                                action={isFiltered ? <ButtonLink href={event.links.guests} variant="secondary">{t('guests.filter.clear')}</ButtonLink> : <Button type="button" onClick={openCreateDialog}>{t('guests.index.addFirst')}</Button>}
                            />
                        </Card>
                    ) : (
                        <>
                            <div className="mt-6 grid gap-3">
                                {guests.data.map((guest) => (
                                    <article key={`${guest.links.update}-${guest.name}`} className="rounded-xl bg-surface p-4 shadow-sm sm:p-5">
                                        <div className="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                                            <div className="min-w-0 space-y-3">
                                                <div className="flex flex-wrap items-center gap-3">
                                                    <h3 className="break-words text-base font-semibold text-ink">{guest.name}</h3>
                                                    <StatusBadge status={guest.status}>{t(`guests.status.${guest.status}` as TranslationKey)}</StatusBadge>
                                                </div>
                                                <p className="text-sm leading-6 text-muted">{companionSummary(guest, t, tp)}</p>
                                            </div>
                                            <div className="grid gap-2 sm:grid-cols-2 xl:grid-cols-4 xl:w-auto xl:min-w-[36rem]">
                                                <Button type="button" variant="secondary" onClick={() => void copyInvitation(guest)} aria-label={t('guests.actions.copyInvitationFor', { name: guest.name })}>
                                                    {t('guests.actions.copyInvitation')}
                                                </Button>
                                                <Button type="button" variant="secondary" onClick={() => setCompanionGuest(guest)} aria-label={t('guests.actions.companionsFor', { name: guest.name })}>
                                                    {t('guests.actions.companions')}
                                                </Button>
                                                <Button type="button" variant="secondary" onClick={() => openEditDialog(guest)} aria-label={t('guests.actions.editFor', { name: guest.name })}>
                                                    {t('guests.actions.edit')}
                                                </Button>
                                                <Button type="button" variant="danger" onClick={() => setDeletingGuest(guest)} aria-label={t('guests.actions.deleteFor', { name: guest.name })}>
                                                    {t('guests.actions.delete')}
                                                </Button>
                                            </div>
                                        </div>
                                    </article>
                                ))}
                            </div>

                            {guests.last_page > 1 ? (
                                <nav className="mt-6 flex flex-col gap-3 border-t border-border pt-5 text-sm text-muted sm:flex-row sm:items-center sm:justify-between" aria-label={t('guests.index.paginationLabel')}>
                                    <p>{t('guests.index.paginationSummary', { from: guests.from ?? 0, to: guests.to ?? 0, total: guests.total })}</p>
                                    <div className="flex flex-wrap items-center gap-2">
                                        {guests.prev_page_url ? (
                                            <ButtonLink href={guests.prev_page_url} variant="secondary">{t('guests.index.previous')}</ButtonLink>
                                        ) : (
                                            <span className="inline-flex min-h-11 items-center justify-center rounded-lg border border-border bg-surface px-4 py-2 text-sm font-semibold text-muted opacity-55" aria-disabled="true">
                                                {t('guests.index.previous')}
                                            </span>
                                        )}
                                        <span className="px-2 font-medium text-ink">
                                            {t('guests.index.pageStatus', { page: guests.current_page, pages: guests.last_page })}
                                        </span>
                                        {guests.next_page_url ? (
                                            <ButtonLink href={guests.next_page_url} variant="secondary">{t('guests.index.next')}</ButtonLink>
                                        ) : (
                                            <span className="inline-flex min-h-11 items-center justify-center rounded-lg border border-border bg-surface px-4 py-2 text-sm font-semibold text-muted opacity-55" aria-disabled="true">
                                                {t('guests.index.next')}
                                            </span>
                                        )}
                                    </div>
                                </nav>
                            ) : null}
                        </>
                    )}
                </section>
            </main>

            <Dialog
                open={createOpen}
                onClose={() => setCreateOpen(false)}
                title={t('guests.create.title')}
                description={t('guests.create.description')}
                cancelLabel={t('guests.form.cancel')}
                confirmLabel={createForm.processing ? t('guests.create.creating') : t('guests.create.submit')}
                onConfirm={createGuest}
                closeOnConfirm={false}
                confirmDisabled={createForm.processing}
            >
                <div className="mt-5 space-y-4">
                    {createErrors.length > 0 ? <FormErrorSummary title={t('auth.formErrorTitle')} errors={createErrors} /> : null}
                    <Field id="guest-create-name" label={t('guests.form.name')} required error={createForm.errors.name}>
                        <TextInput id="guest-create-name" value={createForm.data.name} maxLength={120} autoComplete="name" invalid={Boolean(createForm.errors.name)} onChange={(event) => createForm.setData('name', event.target.value)} />
                    </Field>
                </div>
            </Dialog>

            <Dialog
                open={editingGuest !== null}
                onClose={() => setEditingGuest(null)}
                title={t('guests.edit.title')}
                description={t('guests.edit.description')}
                cancelLabel={t('guests.form.cancel')}
                confirmLabel={editForm.processing ? t('guests.edit.saving') : t('guests.edit.submit')}
                onConfirm={updateGuest}
                closeOnConfirm={false}
                confirmDisabled={editForm.processing}
            >
                <div className="mt-5 space-y-4">
                    {editErrors.length > 0 ? <FormErrorSummary title={t('auth.formErrorTitle')} errors={editErrors} /> : null}
                    <Field id="guest-edit-name" label={t('guests.form.name')} required error={editForm.errors.name}>
                        <TextInput id="guest-edit-name" value={editForm.data.name} maxLength={120} autoComplete="name" invalid={Boolean(editForm.errors.name)} onChange={(event) => editForm.setData('name', event.target.value)} />
                    </Field>
                    <Field id="guest-edit-status" label={t('guests.form.status')} required help={t('guests.form.statusHelp')} error={editForm.errors.status}>
                        <Select id="guest-edit-status" value={editForm.data.status} invalid={Boolean(editForm.errors.status)} onChange={(event) => updateEditStatus(event.target.value as GuestStatus)}>
                            {statusOptions.map((option) => (
                                <option key={option.value} value={option.value}>{t(option.label_key as TranslationKey)}</option>
                            ))}
                        </Select>
                    </Field>
                    {editForm.data.status === 'confirmed' ? (
                        <div className="grid gap-4 sm:grid-cols-2">
                            <Field id="guest-edit-adults" label={t('guests.form.adultCompanions')} error={editForm.errors.adult_companions}>
                                <TextInput id="guest-edit-adults" type="number" inputMode="numeric" min={0} max={5} value={editForm.data.adult_companions} invalid={Boolean(editForm.errors.adult_companions)} onChange={(event) => editForm.setData('adult_companions', Number(event.target.value || 0))} />
                            </Field>
                            <Field id="guest-edit-children" label={t('guests.form.childCompanions')} error={editForm.errors.child_companions}>
                                <TextInput id="guest-edit-children" type="number" inputMode="numeric" min={0} max={5} value={editForm.data.child_companions} invalid={Boolean(editForm.errors.child_companions)} onChange={(event) => editForm.setData('child_companions', Number(event.target.value || 0))} />
                            </Field>
                        </div>
                    ) : (
                        <Alert title={t('guests.form.countsClearedTitle')} tone="info">{t('guests.form.countsClearedDescription')}</Alert>
                    )}
                </div>
            </Dialog>

            <Dialog
                open={deletingGuest !== null}
                onClose={() => setDeletingGuest(null)}
                title={t('guests.delete.title')}
                description={deletingGuest ? t('guests.delete.description', { name: deletingGuest.name }) : undefined}
                cancelLabel={t('guests.delete.cancel')}
                confirmLabel={deleteForm.processing ? t('guests.delete.deleting') : t('guests.delete.confirm')}
                onConfirm={deleteGuest}
                destructive
                closeOnConfirm={false}
                confirmDisabled={deleteForm.processing}
            />

            <Dialog
                open={companionGuest !== null}
                onClose={() => setCompanionGuest(null)}
                title={companionGuest ? t('guests.companions.modalTitle', { name: companionGuest.name }) : t('guests.companions.title')}
                description={t('guests.companions.modalDescription')}
                cancelLabel={t('guests.companions.close')}
            >
                <div className="mt-5">
                    {companionGuest && companionGuest.companions.length > 0 ? (
                        <ul className="divide-y divide-border rounded-lg border border-border" aria-label={t('guests.companions.listLabel')}>
                            {companionGuest.companions.map((companion, index) => (
                                <li key={`${companion.name}-${index}`} className="flex items-center justify-between gap-4 px-4 py-3">
                                    <span className="min-w-0 break-words text-sm font-medium text-ink">{companion.name}</span>
                                    <span className="shrink-0 rounded-full bg-surface-muted px-3 py-1 text-xs font-semibold text-muted">
                                        {companion.is_child ? t('guests.companions.child') : t('guests.companions.adult')}
                                    </span>
                                </li>
                            ))}
                        </ul>
                    ) : (
                        <p className="rounded-lg border border-border bg-surface-muted px-4 py-3 text-sm leading-6 text-muted">
                            {t('guests.companions.none')}
                        </p>
                    )}
                </div>
            </Dialog>

        </AuthenticatedLayout>
    );
}

function FullGuestListView({
    items,
    pagination,
    sort,
    onSortChange,
    t,
}: {
    items: FullGuestListItem[];
    pagination: PaginatedGuests;
    sort: FullGuestListSort;
    onSortChange: (sort: FullGuestListSort) => void;
    t: ReturnType<typeof useLocale>['t'];
}) {
    return (
        <div className="mt-6 space-y-5">
            <div className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h3 className="text-base font-semibold text-ink">{t('dashboard.fullList.title')}</h3>
                    <p className="mt-1 max-w-2xl text-sm leading-6 text-muted">{t('dashboard.fullList.description')}</p>
                </div>
                <div className="sm:text-right">
                    <p className="mb-2 text-sm font-semibold text-ink">{t('dashboard.fullList.sortLabel')}</p>
                    <div className="flex flex-wrap gap-2 sm:justify-end">
                        {fullListSortOptions.map((option) => (
                            <button
                                key={option}
                                type="button"
                                onClick={() => onSortChange(option)}
                                className={`inline-flex min-h-11 items-center rounded-lg px-4 py-2 text-sm font-semibold transition-colors focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-focus ${sort === option ? 'bg-accent text-accent-contrast' : 'border border-border bg-surface text-ink hover:bg-surface-muted'}`}
                                aria-pressed={sort === option}
                            >
                                {t(`dashboard.fullList.sort.${option}` as TranslationKey)}
                            </button>
                        ))}
                    </div>
                </div>
            </div>

            {items.length > 0 ? (
                <ul className="grid gap-3" aria-label={t('dashboard.fullList.listLabel')}>
                    {items.map((item, index) => (
                        <li key={`${item.primary_guest}-${item.name}-${index}`} className="grid gap-3 rounded-xl bg-surface p-4 shadow-sm sm:grid-cols-[1fr_auto] sm:items-center sm:p-5">
                            <div className="min-w-0">
                                <p className="break-words text-base font-semibold text-ink">{fullListDisplayName(item, t)}</p>
                                {!item.is_primary ? (
                                    <p className="mt-1 break-words text-sm text-muted">{t('dashboard.fullList.primaryGuest', { name: item.primary_guest })}</p>
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

            {pagination.last_page > 1 ? (
                <nav className="flex flex-col gap-3 border-t border-border pt-5 text-sm text-muted sm:flex-row sm:items-center sm:justify-between" aria-label={t('guests.index.paginationLabel')}>
                    <p>{t('guests.fullList.pageSummary', { from: pagination.from ?? 0, to: pagination.to ?? 0, total: pagination.total })}</p>
                    <div className="flex flex-wrap items-center gap-2">
                        {pagination.prev_page_url ? (
                            <ButtonLink href={pagination.prev_page_url} variant="secondary">{t('guests.index.previous')}</ButtonLink>
                        ) : (
                            <span className="inline-flex min-h-11 items-center justify-center rounded-lg border border-border bg-surface px-4 py-2 text-sm font-semibold text-muted opacity-55" aria-disabled="true">
                                {t('guests.index.previous')}
                            </span>
                        )}
                        <span className="px-2 font-medium text-ink">
                            {t('guests.index.pageStatus', { page: pagination.current_page, pages: pagination.last_page })}
                        </span>
                        {pagination.next_page_url ? (
                            <ButtonLink href={pagination.next_page_url} variant="secondary">{t('guests.index.next')}</ButtonLink>
                        ) : (
                            <span className="inline-flex min-h-11 items-center justify-center rounded-lg border border-border bg-surface px-4 py-2 text-sm font-semibold text-muted opacity-55" aria-disabled="true">
                                {t('guests.index.next')}
                            </span>
                        )}
                    </div>
                </nav>
            ) : null}
        </div>
    );
}

function FilterLink({ href, active, label }: { href: string; active: boolean; label: string }) {
    return (
        <Link
            href={href}
            preserveScroll
            className={`inline-flex min-h-11 items-center rounded-lg px-4 py-2 text-sm font-semibold transition-colors focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-focus ${active ? 'bg-accent text-accent-contrast' : 'border border-border bg-surface text-ink hover:bg-surface-muted'}`}
            aria-current={active ? 'page' : undefined}
        >
            {label}
        </Link>
    );
}

function companionSummary(guest: GuestListItem, t: ReturnType<typeof useLocale>['t'], translatePlural: ReturnType<typeof useLocale>['tp']) {
    if (guest.status !== 'confirmed') {
        return t('guests.companions.none');
    }

    if (guest.companion_count === 0) {
        return t('guests.companions.justGuest');
    }

    return t('guests.companions.summary', {
        total: translatePlural('companions.count', guest.companion_count),
        adults: translatePlural('adultCompanions.count', guest.adult_companions),
        children: translatePlural('childCompanions.count', guest.child_companions),
    });
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

        return compareText(sortableFullListName(a), sortableFullListName(b));
    });
}

function fullListDisplayName(item: FullGuestListItem, t: ReturnType<typeof useLocale>['t']): string {
    if (item.name) {
        return item.name;
    }

    return item.is_child ? t('dashboard.fullList.unnamedChild') : t('dashboard.fullList.unnamedAdult');
}

function sortableFullListName(item: FullGuestListItem): string {
    return item.name ?? `${item.primary_guest} ${item.is_child ? 'child' : 'adult'}`;
}

function compareText(a: string, b: string): number {
    return a.localeCompare(b, undefined, { sensitivity: 'base' });
}

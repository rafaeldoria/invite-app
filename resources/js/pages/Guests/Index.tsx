import { Head, Link, useForm } from '@inertiajs/react';
import { useState } from 'react';
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
import type { GuestFormData, GuestListItem, GuestStatus, GuestStatusOption, PaginatedGuests } from '../../types/guests';
import type { TranslationKey } from '../../locales';

type Feedback = { tone: 'success' | 'error'; message: string } | null;

type Props = {
    event: {
        name: string;
        links: {
            show: string;
            guests: string;
        };
    };
    guests: PaginatedGuests;
    filters: {
        status: GuestStatus | null;
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

export default function Index({ event, guests, filters, statusOptions, links }: Props) {
    const { t, tp } = useLocale();
    const [createOpen, setCreateOpen] = useState(false);
    const [editingGuest, setEditingGuest] = useState<GuestListItem | null>(null);
    const [deletingGuest, setDeletingGuest] = useState<GuestListItem | null>(null);
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
    const hasGuests = guests.data.length > 0;
    const isFiltered = filters.status !== null;

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
        try {
            await copyText(guest.invitation_url);
            setFeedback({ tone: 'success', message: t('guests.feedback.copySuccess', { name: guest.name }) });
        } catch {
            setFeedback({ tone: 'error', message: t('guests.feedback.copyError') });
        }
    }

    function filterHref(status: GuestStatus | 'all') {
        return status === 'all' ? event.links.guests : `${event.links.guests}?status=${status}`;
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
                                {guests.total > 0
                                    ? t('guests.index.listSummary', { from: guests.from ?? 0, to: guests.to ?? 0, total: guests.total })
                                    : t('guests.index.noGuestsSummary')}
                            </p>
                        </div>
                        <nav className="flex flex-wrap gap-2" aria-label={t('guests.index.filterLabel')}>
                            <FilterLink href={filterHref('all')} active={selectedFilter === 'all'} label={t('guests.filter.all')} />
                            {statusOptions.map((option) => (
                                <FilterLink key={option.value} href={filterHref(option.value)} active={selectedFilter === option.value} label={t(option.label_key as TranslationKey)} />
                            ))}
                        </nav>
                    </div>

                    {!hasGuests ? (
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
                                            <div className="grid gap-2 sm:grid-cols-3 lg:w-auto lg:min-w-[28rem]">
                                                <Button type="button" variant="secondary" onClick={() => copyInvitation(guest)} aria-label={t('guests.actions.copyFor', { name: guest.name })}>
                                                    {t('guests.actions.copy')}
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
        </AuthenticatedLayout>
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

async function copyText(value: string): Promise<void> {
    if (navigator.clipboard?.writeText) {
        try {
            await navigator.clipboard.writeText(value);
            return;
        } catch {
            // Continue to the textarea fallback for browsers that expose Clipboard but reject it.
        }
    }

    const textarea = document.createElement('textarea');
    textarea.value = value;
    textarea.setAttribute('readonly', 'true');
    textarea.style.position = 'fixed';
    textarea.style.inset = '0 auto auto 0';
    textarea.style.opacity = '0';
    document.body.appendChild(textarea);
    textarea.select();

    try {
        if (!document.execCommand('copy')) {
            throw new Error('Copy command failed.');
        }
    } finally {
        document.body.removeChild(textarea);
    }
}

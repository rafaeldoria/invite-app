import { Head, Link, useForm } from '@inertiajs/react';
import { useMemo, type FormEvent } from 'react';
import { Alert } from '../../components/feedback/Alert';
import { FormErrorSummary } from '../../components/forms/FormErrorSummary';
import { Field } from '../../components/forms/Field';
import { Checkbox, TextInput } from '../../components/forms/controls';
import { Button } from '../../components/ui/Button';
import { PublicLayout } from '../../layouts/PublicLayout';
import { useLocale } from '../../hooks/use-locale';
import type { RsvpAttendance, RsvpCompanion, RsvpFormData, RsvpFormProps } from '../../types/rsvp';
import { formatDate, formatTime } from '../../utils/formatting';

const maxCompanions = 5;

export default function Form({ event, rsvp }: RsvpFormProps) {
    const { locale, t, tp } = useLocale();
    const form = useForm<RsvpFormData>({
        name: rsvp.initial.name,
        attendance: rsvp.initial.attendance,
        adult_companions: String(rsvp.initial.adult_companions),
        child_companions: String(rsvp.initial.child_companions),
        companions: rsvp.initial.companions,
        response_token: rsvp.response_token ?? '',
    });

    const isConfirmed = form.data.attendance === 'confirmed';
    const errors = useMemo(() => {
        const fieldIds: Partial<Record<keyof RsvpFormData, string>> = {
            name: 'rsvp-name',
            attendance: 'rsvp-attendance-confirmed',
            adult_companions: 'rsvp-companions',
            child_companions: 'rsvp-companions',
            companions: 'rsvp-companions',
        };

        return Object.entries(form.errors).map(([field, message]) => ({
            fieldId: companionFieldId(field) ?? fieldIds[field as keyof RsvpFormData] ?? 'rsvp-form-title',
            message,
        }));
    }, [form.errors]);

    function chooseAttendance(attendance: RsvpAttendance) {
        form.setData((data) => ({
            ...data,
            attendance,
            adult_companions: attendance === 'declined' ? '0' : String(adultCompanionCount(data.companions)),
            child_companions: attendance === 'declined' ? '0' : String(childCompanionCount(data.companions)),
            companions: attendance === 'declined' ? [] : data.companions,
        }));
    }

    function addCompanion() {
        if (form.data.companions.length >= maxCompanions) {
            return;
        }

        updateCompanions([...form.data.companions, { name: '', is_child: false }]);
    }

    function updateCompanion(index: number, companion: RsvpCompanion) {
        updateCompanions(form.data.companions.map((current, currentIndex) => (currentIndex === index ? companion : current)));
    }

    function removeCompanion(index: number) {
        updateCompanions(form.data.companions.filter((_, currentIndex) => currentIndex !== index));
    }

    function updateCompanions(companions: RsvpCompanion[]) {
        form.setData((data) => ({
            ...data,
            companions,
            adult_companions: String(adultCompanionCount(companions)),
            child_companions: String(childCompanionCount(companions)),
        }));
    }

    function submit(event: FormEvent<HTMLFormElement>) {
        event.preventDefault();
        const companions = form.data.attendance === 'declined' ? [] : form.data.companions;

        const data = {
            ...form.data,
            companions,
            adult_companions: form.data.attendance === 'declined' ? '0' : String(adultCompanionCount(companions)),
            child_companions: form.data.attendance === 'declined' ? '0' : String(childCompanionCount(companions)),
        };

        form.transform(() => data);

        if (rsvp.method === 'patch') {
            form.patch(rsvp.submit_url, { preserveScroll: true });
            return;
        }

        form.post(rsvp.submit_url, { preserveScroll: true });
    }

    const title = rsvp.receipt ? t('rsvp.receipt.title') : t('rsvp.form.title');
    const companionCount = form.data.companions.length;
    const partySize = isConfirmed ? 1 + companionCount : 0;
    const companionErrors = form.errors as Record<string, string | undefined>;

    return (
        <PublicLayout>
            <Head title={`${title} - ${event.name}`} />

            <main id="main-content" className="mx-auto grid w-full max-w-5xl gap-6 px-5 py-6 lg:grid-cols-[minmax(0,1fr)_22rem] lg:items-start lg:py-10">
                <section className="min-w-0 rounded-xl bg-surface p-5 shadow-sm sm:p-7" aria-labelledby="rsvp-form-title">
                    <div className="space-y-2">
                        {rsvp.event_url ? (
                            <Link href={rsvp.event_url} className="inline-flex rounded-md text-sm font-semibold text-accent-strong focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-focus">
                                {t('rsvp.form.backToEvent')}
                            </Link>
                        ) : null}
                        <h1 id="rsvp-form-title" className="text-2xl font-bold tracking-[-0.02em] text-ink">{title}</h1>
                        <p className="max-w-2xl text-sm leading-6 text-muted">{t('rsvp.form.description')}</p>
                    </div>

                    {rsvp.receipt ? (
                        <div className="mt-6">
                            <Alert title={t('rsvp.receipt.savedTitle')} tone="success">
                                {receiptSummary(rsvp.receipt.status, rsvp.receipt.party_size, t, tp)}
                            </Alert>
                        </div>
                    ) : null}

                    {errors.length > 0 ? (
                        <div className="mt-6">
                            <FormErrorSummary title={t('auth.formErrorTitle')} errors={errors} />
                        </div>
                    ) : null}

                    <form onSubmit={submit} className="mt-6 space-y-6" noValidate>
                        {rsvp.name_locked ? (
                            <div className="rounded-lg bg-canvas p-4">
                                <p className="text-sm font-semibold text-ink">{t('rsvp.form.invitedGuest')}</p>
                                <p className="mt-1 break-words text-base text-muted">{rsvp.guest_name}</p>
                            </div>
                        ) : (
                            <Field id="rsvp-name" label={t('rsvp.form.name')} required error={form.errors.name}>
                                <TextInput id="rsvp-name" value={form.data.name} maxLength={120} invalid={Boolean(form.errors.name)} onChange={(change) => form.setData('name', change.target.value)} autoComplete="name" />
                            </Field>
                        )}

                        <fieldset className="space-y-3" aria-describedby={form.errors.attendance ? 'rsvp-attendance-error' : undefined}>
                            <legend className="text-sm font-semibold text-ink">{t('rsvp.form.attendance')}</legend>
                            <div className="grid gap-3 sm:grid-cols-2">
                                <AttendanceOption
                                    id="rsvp-attendance-confirmed"
                                    name="attendance"
                                    checked={form.data.attendance === 'confirmed'}
                                    title={t('rsvp.form.confirm')}
                                    description={t('rsvp.form.confirmDescription')}
                                    onChange={() => chooseAttendance('confirmed')}
                                />
                                <AttendanceOption
                                    id="rsvp-attendance-declined"
                                    name="attendance"
                                    checked={form.data.attendance === 'declined'}
                                    title={t('rsvp.form.decline')}
                                    description={t('rsvp.form.declineDescription')}
                                    onChange={() => chooseAttendance('declined')}
                                />
                            </div>
                            {form.errors.attendance ? <p id="rsvp-attendance-error" className="text-sm font-medium text-danger-ink" role="alert">{form.errors.attendance}</p> : null}
                        </fieldset>

                        {isConfirmed ? (
                            <section id="rsvp-companions" className="space-y-3" aria-labelledby="rsvp-companions-title">
                                <div className="space-y-1">
                                    <h2 id="rsvp-companions-title" className="text-sm font-semibold text-ink">{t('rsvp.form.companionsTitle')}</h2>
                                    <p className="text-sm leading-6 text-muted">{t('rsvp.form.companionHelp')}</p>
                                </div>

                                {form.data.companions.map((companion, index) => {
                                    const nameId = `rsvp-companion-${index}-name`;
                                    const nameError = companionErrors[`companions.${index}.name`];

                                    return (
                                        <div key={index} className="rounded-lg border border-border bg-canvas p-4">
                                            <div className="flex items-start justify-between gap-3">
                                                <p className="text-sm font-semibold text-ink">{t('rsvp.form.companionNumber', { number: String(index + 1) })}</p>
                                                <button type="button" onClick={() => removeCompanion(index)} className="inline-flex min-h-11 items-center rounded-md px-3 py-2 text-sm font-semibold text-danger-ink hover:bg-danger-soft focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-focus">
                                                    {t('rsvp.form.removeCompanion')}
                                                </button>
                                            </div>
                                            <div className="mt-3 grid gap-3 sm:grid-cols-[minmax(0,1fr)_8rem] sm:items-start">
                                                <Field id={nameId} label={t('rsvp.form.companionName')} required error={nameError}>
                                                    <TextInput
                                                        id={nameId}
                                                        value={companion.name}
                                                        maxLength={120}
                                                        invalid={Boolean(nameError)}
                                                        onChange={(change) => updateCompanion(index, { ...companion, name: change.target.value })}
                                                        autoComplete="name"
                                                    />
                                                </Field>
                                                <div className="pt-1 sm:pt-8">
                                                    <Checkbox
                                                        label={t('rsvp.form.childCompanion')}
                                                        checked={companion.is_child}
                                                        onChange={(change) => updateCompanion(index, { ...companion, is_child: change.target.checked })}
                                                    />
                                                </div>
                                            </div>
                                        </div>
                                    );
                                })}

                                {companionErrors.companions ? <p className="text-sm font-medium text-danger-ink" role="alert">{companionErrors.companions}</p> : null}

                                {form.data.companions.length < maxCompanions ? (
                                    <button type="button" onClick={addCompanion} className="flex min-h-24 w-full items-center justify-between gap-4 rounded-xl border border-border-strong bg-surface px-5 py-4 text-left transition-colors hover:border-accent focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-focus">
                                        <span className="text-base font-semibold text-ink">{t('rsvp.form.addCompanion')}</span>
                                        <span aria-hidden="true" className="text-4xl font-light leading-none text-focus">+</span>
                                    </button>
                                ) : (
                                    <Alert title={t('rsvp.form.companionLimitTitle')} tone="info">{t('rsvp.form.companionLimitDescription')}</Alert>
                                )}
                            </section>
                        ) : form.data.attendance === 'declined' ? (
                            <Alert title={t('rsvp.form.countsClearedTitle')} tone="info">{t('rsvp.form.countsClearedDescription')}</Alert>
                        ) : null}

                        <div className="rounded-lg bg-canvas p-4 text-sm text-muted">
                            <p className="font-semibold text-ink">{t('rsvp.form.partySummaryTitle')}</p>
                            <p className="mt-1">{partySummary(form.data.attendance, partySize, companionCount, t, tp)}</p>
                        </div>

                        <Button type="submit" className="w-full sm:w-auto" loading={form.processing} loadingLabel={t('rsvp.form.submitting')}>
                            {t('rsvp.form.submit')}
                        </Button>
                    </form>
                </section>

                <aside className="min-w-0 space-y-4 lg:sticky lg:top-6">
                    <section className="rounded-xl bg-surface p-5 shadow-sm" aria-labelledby="rsvp-event-title">
                        <h2 id="rsvp-event-title" className="text-lg font-semibold text-ink">{event.name}</h2>
                        {event.theme ? <p className="mt-1 text-sm font-semibold text-accent-strong">{event.theme}</p> : null}
                        <dl className="mt-4 space-y-3 text-sm">
                            <div>
                                <dt className="font-semibold text-ink">{t('events.fields.startsAt')}</dt>
                                <dd className="mt-1 text-muted">{formatDate(event.starts_at, locale, event.timezone)} · {formatTime(event.starts_at, locale, event.timezone)}</dd>
                            </div>
                            <div>
                                <dt className="font-semibold text-ink">{t('events.fields.location')}</dt>
                                <dd className="mt-1 break-words text-muted">{event.location}</dd>
                            </div>
                        </dl>
                    </section>

                    {rsvp.receipt ? (
                        <section className="rounded-xl bg-surface p-5 shadow-sm" aria-labelledby="rsvp-receipt-title">
                            <h2 id="rsvp-receipt-title" className="text-base font-semibold text-ink">{t('rsvp.receipt.summaryTitle')}</h2>
                            <dl className="mt-4 space-y-3 text-sm">
                                <ReceiptRow label={t('rsvp.receipt.guest')} value={rsvp.receipt.name} />
                                <ReceiptRow label={t('rsvp.receipt.status')} value={statusLabel(rsvp.receipt.status, t)} />
                                <ReceiptRow label={t('rsvp.receipt.partySize')} value={receiptSummary(rsvp.receipt.status, rsvp.receipt.party_size, t, tp)} />
                                <ReceiptRow label={t('rsvp.receipt.updatedAt')} value={`${formatDate(rsvp.receipt.updated_at, locale, event.timezone)} · ${formatTime(rsvp.receipt.updated_at, locale, event.timezone)}`} />
                            </dl>
                            <a href="#rsvp-form-title" className="mt-4 inline-flex min-h-11 items-center justify-center rounded-lg border border-border bg-surface px-4 py-2 text-sm font-semibold text-ink hover:bg-surface-muted focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-focus">
                                {t('rsvp.receipt.updateAction')}
                            </a>
                        </section>
                    ) : null}
                </aside>
            </main>
        </PublicLayout>
    );
}

function AttendanceOption({ id, name, checked, title, description, onChange }: { id: string; name: string; checked: boolean; title: string; description: string; onChange: () => void }) {
    return (
        <label htmlFor={id} className={`block min-h-24 cursor-pointer rounded-lg border p-4 transition-colors focus-within:outline-2 focus-within:outline-offset-2 focus-within:outline-focus ${checked ? 'border-accent bg-accent-soft' : 'border-border bg-canvas hover:border-border-strong'}`}>
            <input id={id} type="radio" name={name} checked={checked} onChange={onChange} className="sr-only" />
            <span className="flex items-start gap-3">
                <span aria-hidden="true" className={`mt-0.5 flex size-5 shrink-0 items-center justify-center rounded-full border bg-surface ${checked ? 'border-accent' : 'border-border-strong'}`}>
                    {checked ? <span className="size-2.5 rounded-full bg-ink" /> : null}
                </span>
                <span className="min-w-0">
                    <span className="block text-base font-semibold text-ink">{title}</span>
                    <span className="mt-1 block text-sm leading-6 text-muted">{description}</span>
                </span>
            </span>
        </label>
    );
}

function ReceiptRow({ label, value }: { label: string; value: string }) {
    return (
        <div>
            <dt className="font-semibold text-ink">{label}</dt>
            <dd className="mt-1 break-words text-muted">{value}</dd>
        </div>
    );
}

function partySummary(attendance: RsvpAttendance | '', partySize: number, companionCount: number, t: ReturnType<typeof useLocale>['t'], tp: ReturnType<typeof useLocale>['tp']): string {
    if (attendance === 'declined') {
        return t('rsvp.summary.declined');
    }

    if (attendance === 'confirmed') {
        return t('rsvp.summary.confirmed', {
            party: tp('guests.count', partySize),
            companions: tp('companions.count', companionCount),
        });
    }

    return t('rsvp.summary.pending');
}

function receiptSummary(status: RsvpAttendance, partySize: number, t: ReturnType<typeof useLocale>['t'], tp: ReturnType<typeof useLocale>['tp']): string {
    if (status === 'declined') {
        return t('rsvp.receipt.declined');
    }

    return t('rsvp.receipt.confirmed', { party: tp('guests.count', partySize) });
}

function statusLabel(status: RsvpAttendance, t: ReturnType<typeof useLocale>['t']): string {
    return status === 'confirmed' ? t('guests.status.confirmed') : t('guests.status.declined');
}

function adultCompanionCount(companions: RsvpCompanion[]): number {
    return companions.filter((companion) => !companion.is_child).length;
}

function childCompanionCount(companions: RsvpCompanion[]): number {
    return companions.filter((companion) => companion.is_child).length;
}

function companionFieldId(field: string): string | undefined {
    const match = /^companions\.(\d+)\.name$/.exec(field);

    return match ? `rsvp-companion-${match[1]}-name` : undefined;
}

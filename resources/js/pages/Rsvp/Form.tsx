import { Head, Link, useForm } from '@inertiajs/react';
import { useMemo, type FormEvent } from 'react';
import { Alert } from '../../components/feedback/Alert';
import { FormErrorSummary } from '../../components/forms/FormErrorSummary';
import { Field } from '../../components/forms/Field';
import { TextInput } from '../../components/forms/controls';
import { Button } from '../../components/ui/Button';
import { PublicLayout } from '../../layouts/PublicLayout';
import { useLocale } from '../../hooks/use-locale';
import type { RsvpAttendance, RsvpFormData, RsvpFormProps } from '../../types/rsvp';
import { formatDate, formatTime } from '../../utils/formatting';

export default function Form({ event, rsvp }: RsvpFormProps) {
    const { locale, t, tp } = useLocale();
    const form = useForm<RsvpFormData>({
        name: rsvp.initial.name,
        attendance: rsvp.initial.attendance,
        adult_companions: String(rsvp.initial.adult_companions),
        child_companions: String(rsvp.initial.child_companions),
        response_token: rsvp.response_token ?? '',
    });

    const isConfirmed = form.data.attendance === 'confirmed';
    const errors = useMemo(() => {
        const fieldIds: Partial<Record<keyof RsvpFormData, string>> = {
            name: 'rsvp-name',
            attendance: 'rsvp-attendance-confirmed',
            adult_companions: 'rsvp-adult-companions',
            child_companions: 'rsvp-child-companions',
        };

        return Object.entries(form.errors).map(([field, message]) => ({
            fieldId: fieldIds[field as keyof RsvpFormData] ?? 'rsvp-form-title',
            message,
        }));
    }, [form.errors]);

    function chooseAttendance(attendance: RsvpAttendance) {
        form.setData((data) => ({
            ...data,
            attendance,
            adult_companions: attendance === 'declined' ? '0' : data.adult_companions,
            child_companions: attendance === 'declined' ? '0' : data.child_companions,
        }));
    }

    function submit(event: FormEvent<HTMLFormElement>) {
        event.preventDefault();

        const data = {
            ...form.data,
            adult_companions: form.data.attendance === 'declined' ? '0' : form.data.adult_companions,
            child_companions: form.data.attendance === 'declined' ? '0' : form.data.child_companions,
        };

        form.transform(() => data);

        if (rsvp.method === 'patch') {
            form.patch(rsvp.submit_url, { preserveScroll: true });
            return;
        }

        form.post(rsvp.submit_url, { preserveScroll: true });
    }

    const title = rsvp.receipt ? t('rsvp.receipt.title') : t('rsvp.form.title');
    const companionCount = numberValue(form.data.adult_companions) + numberValue(form.data.child_companions);
    const partySize = isConfirmed ? 1 + companionCount : 0;

    return (
        <PublicLayout>
            <Head title={`${title} - ${event.name}`} />

            <main id="main-content" className="mx-auto grid w-full max-w-5xl gap-6 px-5 py-6 lg:grid-cols-[minmax(0,1fr)_22rem] lg:items-start lg:py-10">
                <section className="min-w-0 rounded-xl bg-surface p-5 shadow-sm sm:p-7" aria-labelledby="rsvp-form-title">
                    <div className="space-y-2">
                        <Link href={event.canonical_url} className="inline-flex rounded-md text-sm font-semibold text-accent-strong focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-focus">
                            {t('rsvp.form.backToEvent')}
                        </Link>
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

                    <form onSubmit={submit} className="mt-6 space-y-6">
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
                            <div className="grid gap-4 sm:grid-cols-2">
                                <Field id="rsvp-adult-companions" label={t('rsvp.form.adultCompanions')} help={t('rsvp.form.companionHelp')} error={form.errors.adult_companions}>
                                    <TextInput id="rsvp-adult-companions" type="number" min={0} max={20} inputMode="numeric" value={form.data.adult_companions} invalid={Boolean(form.errors.adult_companions)} onChange={(change) => form.setData('adult_companions', change.target.value)} />
                                </Field>
                                <Field id="rsvp-child-companions" label={t('rsvp.form.childCompanions')} help={t('rsvp.form.companionHelp')} error={form.errors.child_companions}>
                                    <TextInput id="rsvp-child-companions" type="number" min={0} max={20} inputMode="numeric" value={form.data.child_companions} invalid={Boolean(form.errors.child_companions)} onChange={(change) => form.setData('child_companions', change.target.value)} />
                                </Field>
                            </div>
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
        <label htmlFor={id} className={`block min-h-24 cursor-pointer rounded-lg border p-4 transition-colors ${checked ? 'border-accent bg-accent-soft' : 'border-border bg-canvas hover:border-border-strong'}`}>
            <input id={id} type="radio" name={name} checked={checked} onChange={onChange} className="sr-only" />
            <span className="block text-base font-semibold text-ink">{title}</span>
            <span className="mt-1 block text-sm leading-6 text-muted">{description}</span>
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

function numberValue(value: string): number {
    const parsed = Number(value);

    return Number.isFinite(parsed) ? parsed : 0;
}

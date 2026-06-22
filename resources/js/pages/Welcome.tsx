import { Head, usePage } from '@inertiajs/react';
import { useState, type FormEvent } from 'react';
import { Alert } from '../components/feedback/Alert';
import { EmptyState } from '../components/feedback/EmptyState';
import { FormErrorSummary } from '../components/forms/FormErrorSummary';
import { Field } from '../components/forms/Field';
import { Checkbox, Textarea, TextInput } from '../components/forms/controls';
import { Button } from '../components/ui/Button';
import { Card } from '../components/ui/Card';
import { Dialog } from '../components/ui/Dialog';
import { LoadingIndicator, Skeleton } from '../components/ui/Loading';
import { StatusBadge } from '../components/ui/StatusBadge';
import { useLocale } from '../hooks/use-locale';
import { AuthenticatedLayout } from '../layouts/AuthenticatedLayout';
import { PublicLayout } from '../layouts/PublicLayout';
import type { SharedPageProps } from '../types/shared';

export default function Welcome() {
    const { auth } = usePage<SharedPageProps>().props;
    const { t } = useLocale();
    const [dialogOpen, setDialogOpen] = useState(false);
    const [showErrors, setShowErrors] = useState(false);
    const [submitting, setSubmitting] = useState(false);
    const [saved, setSaved] = useState(false);
    const Layout = auth.user ? AuthenticatedLayout : PublicLayout;
    const errors = showErrors ? [{ fieldId: 'event-name', message: t('welcome.nameError') }] : [];

    function submitExample(event: FormEvent<HTMLFormElement>) {
        event.preventDefault();
        const hasName = new FormData(event.currentTarget).get('name')?.toString().trim();
        setShowErrors(!hasName);
        setSaved(false);

        if (!hasName) return;

        setSubmitting(true);
        window.setTimeout(() => {
            setSubmitting(false);
            setSaved(true);
        }, 500);
    }

    return (
        <Layout>
            <Head title={t('welcome.foundation')} />
            <main id="main-content" className="mx-auto max-w-6xl px-5 py-10 sm:py-14 lg:py-18">
                <header className="max-w-3xl">
                    <p className="text-sm font-semibold text-accent-strong">{t('welcome.foundation')}</p>
                    <h1 className="mt-3 text-3xl font-bold tracking-[-0.03em] text-balance text-ink sm:text-4xl lg:text-5xl">{t('welcome.title')}</h1>
                    <p className="mt-5 max-w-[68ch] text-base leading-7 text-pretty text-muted sm:text-lg">{t('welcome.description')}</p>
                </header>

                <div className="mt-10 flex flex-col gap-6 lg:flex-row lg:items-start">
                    <Card className="min-w-0 flex-1">
                        <h2 className="text-xl font-bold tracking-[-0.02em]">{t('welcome.formTitle')}</h2>
                        <p className="mt-2 text-sm leading-6 text-muted">{t('welcome.formDescription')}</p>
                        <form className="mt-6 space-y-5" onSubmit={submitExample} noValidate>
                            <FormErrorSummary title={t('welcome.alertTitle')} errors={errors} />
                            {saved ? <Alert title={t('welcome.formSuccess')} tone="success">{t('welcome.formDescription')}</Alert> : null}
                            <Field id="event-name" label={t('welcome.name')} required help={t('welcome.nameHelp')} error={showErrors ? t('welcome.nameError') : undefined}>
                                <TextInput id="event-name" name="name" invalid={showErrors} autoComplete="off" required />
                            </Field>
                            <Field id="event-description" label={t('welcome.descriptionLabel')}>
                                <Textarea id="event-description" name="description" />
                            </Field>
                            <Field id="event-address" label={t('welcome.address')}>
                                <TextInput id="event-address" name="address" placeholder={t('welcome.addressPlaceholder')} />
                            </Field>
                            <Checkbox name="reminder" label={t('welcome.reminder')} />
                            <Button type="submit" loading={submitting} loadingLabel={t('welcome.saving')} className="mt-2 !rounded-full !px-6">{t('welcome.save')}</Button>
                        </form>
                    </Card>

                    <div className="min-w-0 space-y-6 lg:w-[22rem]">
                        <Card>
                            <h2 className="text-xl font-bold tracking-[-0.02em]">{t('welcome.feedbackTitle')}</h2>
                            <p className="mt-2 text-sm leading-6 text-muted">{t('welcome.feedbackDescription')}</p>
                            <div className="mt-5 flex flex-wrap gap-2"><StatusBadge status="confirmed">{t('welcome.confirmed')}</StatusBadge><StatusBadge status="pending">{t('welcome.pending')}</StatusBadge><StatusBadge status="declined">{t('welcome.declined')}</StatusBadge></div>
                            <div className="mt-5"><Alert title={t('welcome.alertTitle')} tone="info">{t('welcome.alertBody')}</Alert></div>
                        </Card>
                        <Card>
                            <EmptyState title={t('welcome.emptyTitle')} description={t('welcome.emptyBody')} action={<Button type="button" variant="secondary">{t('welcome.addGuest')}</Button>} />
                        </Card>
                    </div>
                </div>

                <section className="mt-6 rounded-xl bg-surface p-5 shadow-sm sm:p-6" aria-labelledby="loading-title">
                    <div className="flex flex-col gap-5 sm:flex-row sm:items-center sm:justify-between"><LoadingIndicator label={t('welcome.loading')} /><Button type="button" variant="danger" onClick={() => setDialogOpen(true)}>{t('welcome.dialogOpen')}</Button></div>
                    <h2 id="loading-title" className="sr-only">{t('welcome.loading')}</h2>
                    <div className="mt-5 space-y-3"><Skeleton className="h-4 w-2/3" /><Skeleton className="h-4 w-full" /></div>
                </section>
            </main>
            <Dialog open={dialogOpen} onClose={() => setDialogOpen(false)} title={t('welcome.dialogTitle')} description={t('welcome.dialogDescription')} cancelLabel={t('welcome.cancel')} confirmLabel={t('welcome.remove')} onConfirm={() => undefined} destructive />
        </Layout>
    );
}

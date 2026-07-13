import { Head, Link, useForm, usePage } from '@inertiajs/react';
import { useMemo, type FormEvent, type ReactNode } from 'react';
import { FormErrorSummary } from '../../components/forms/FormErrorSummary';
import { Field } from '../../components/forms/Field';
import { Textarea, TextInput } from '../../components/forms/controls';
import { Button, ButtonLink } from '../../components/ui/Button';
import { useLocale } from '../../hooks/use-locale';
import { AuthenticatedLayout } from '../../layouts/AuthenticatedLayout';
import { PublicLayout } from '../../layouts/PublicLayout';
import type { SharedPageProps } from '../../types/shared';

type SupportContactForm = {
    name: string;
    contact: string;
    subject: string;
    message: string;
};

type SupportContactProps = {
    defaults: {
        name: string;
        contact: string;
    };
    links: {
        store: string;
        home: string;
    };
};

export default function Contact({ defaults, links }: SupportContactProps) {
    const { auth } = usePage<SharedPageProps>().props;
    const { t } = useLocale();
    const form = useForm<SupportContactForm>({
        name: defaults.name,
        contact: defaults.contact,
        subject: '',
        message: '',
    });

    const errors = useMemo(() => {
        const fieldIds: Record<keyof SupportContactForm, string> = {
            name: 'support-name',
            contact: 'support-contact',
            subject: 'support-subject',
            message: 'support-message',
        };

        return Object.entries(form.errors).map(([field, message]) => ({
            fieldId: fieldIds[field as keyof SupportContactForm] ?? 'support-title',
            message,
        }));
    }, [form.errors]);

    function submit(event: FormEvent<HTMLFormElement>) {
        event.preventDefault();
        form.post(links.store, { preserveScroll: true });
    }

    const page = (
        <>
            <Head title={t('support.metaTitle')}>
                <meta name="description" content={t('support.metaDescription')} />
            </Head>

            <main id="main-content" className="mx-auto grid w-full max-w-5xl gap-6 px-5 py-8 lg:grid-cols-[minmax(0,1fr)_20rem] lg:items-start lg:py-12">
                <section className="min-w-0 rounded-xl bg-surface p-5 shadow-sm sm:p-7" aria-labelledby="support-title">
                    <div className="space-y-2">
                        <Link href={links.home} className="inline-flex rounded-md text-sm font-semibold text-accent-strong focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-focus">
                            {t('support.backHome')}
                        </Link>
                        <h1 id="support-title" className="text-2xl font-bold tracking-[-0.02em] text-ink">{t('support.title')}</h1>
                        <p className="max-w-2xl text-sm leading-6 text-muted">{t('support.description')}</p>
                    </div>

                    {errors.length > 0 ? (
                        <div className="mt-6">
                            <FormErrorSummary title={t('auth.formErrorTitle')} errors={errors} />
                        </div>
                    ) : null}

                    <form onSubmit={submit} className="mt-6 space-y-5" noValidate>
                        <Field id="support-name" label={t('support.name')} required error={form.errors.name}>
                            <TextInput id="support-name" name="name" value={form.data.name} maxLength={120} autoComplete="name" required invalid={Boolean(form.errors.name)} onChange={(event) => form.setData('name', event.target.value)} />
                        </Field>

                        <Field id="support-contact" label={t('support.contact')} required help={t('support.contactHelp')} error={form.errors.contact}>
                            <TextInput id="support-contact" name="contact" value={form.data.contact} maxLength={255} autoComplete="email" inputMode="email" required invalid={Boolean(form.errors.contact)} onChange={(event) => form.setData('contact', event.target.value)} />
                        </Field>

                        <Field id="support-subject" label={t('support.subject')} required error={form.errors.subject}>
                            <TextInput id="support-subject" name="subject" value={form.data.subject} maxLength={160} required invalid={Boolean(form.errors.subject)} onChange={(event) => form.setData('subject', event.target.value)} />
                        </Field>

                        <Field id="support-message" label={t('support.message')} required help={t('support.messageHelp')} error={form.errors.message}>
                            <Textarea id="support-message" name="message" value={form.data.message} maxLength={4000} required invalid={Boolean(form.errors.message)} onChange={(event) => form.setData('message', event.target.value)} />
                        </Field>

                        <Button type="submit" loading={form.processing} loadingLabel={t('support.submitting')} className="w-full sm:w-auto">
                            {t('support.submit')}
                        </Button>
                    </form>
                </section>

                <aside className="rounded-xl bg-surface-muted p-5" aria-labelledby="support-guidance-title">
                    <h2 id="support-guidance-title" className="text-base font-semibold text-ink">{t('support.guidanceTitle')}</h2>
                    <p className="mt-2 text-sm leading-6 text-muted">{t('support.guidanceDescription')}</p>
                    <div className="mt-5 rounded-lg bg-surface p-4">
                        <p className="text-sm font-semibold text-ink">{t('support.responseTitle')}</p>
                        <p className="mt-1 text-sm leading-6 text-muted">{t('support.responseDescription')}</p>
                    </div>
                </aside>
            </main>
        </>
    );

    if (auth.user) {
        return <AuthenticatedLayout>{page}</AuthenticatedLayout>;
    }

    const headerActions: ReactNode = (
        <ButtonLink href="/login" variant="secondary" className="px-3 sm:px-4">
            {t('welcome.signIn')}
        </ButtonLink>
    );

    return <PublicLayout headerActions={headerActions}>{page}</PublicLayout>;
}

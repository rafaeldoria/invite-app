import { Head, Link, useForm } from '@inertiajs/react';
import { Field } from '../../components/forms/Field';
import { FormErrorSummary } from '../../components/forms/FormErrorSummary';
import { TextInput } from '../../components/forms/controls';
import { Button } from '../../components/ui/Button';
import { useLocale } from '../../hooks/use-locale';
import { AuthenticatedLayout } from '../../layouts/AuthenticatedLayout';

type ChangePasswordForm = {
    current_password: string;
    password: string;
    password_confirmation: string;
};

export default function Password() {
    const { t } = useLocale();
    const form = useForm<ChangePasswordForm>({
        current_password: '',
        password: '',
        password_confirmation: '',
    });
    const errors = [
        form.errors.current_password ? { fieldId: 'current-password', message: form.errors.current_password } : null,
        form.errors.password ? { fieldId: 'new-password', message: form.errors.password } : null,
        form.errors.password_confirmation ? { fieldId: 'password-confirmation', message: form.errors.password_confirmation } : null,
    ].filter((error): error is { fieldId: string; message: string } => error !== null);

    return (
        <AuthenticatedLayout>
            <Head title={t('settings.password.title')} />
            <main id="main-content" className="mx-auto w-full max-w-3xl px-5 py-8 sm:py-10">
                <div>
                    <Link href="/events" className="rounded-md text-sm font-semibold text-accent-strong underline underline-offset-4 focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-focus">
                        {t('settings.backToEvents')}
                    </Link>
                    <h1 className="mt-4 text-2xl font-bold tracking-[-0.02em] text-ink">{t('settings.password.title')}</h1>
                    <p className="mt-2 max-w-2xl text-sm leading-6 text-muted">{t('settings.password.description')}</p>
                </div>

                <form className="mt-6 space-y-5 rounded-xl bg-surface p-5 shadow-sm sm:p-6" onSubmit={(event) => {
                    event.preventDefault();
                    form.patch('/settings/password', {
                        preserveScroll: true,
                        onSuccess: () => form.reset(),
                        onError: () => form.reset('current_password', 'password', 'password_confirmation'),
                    });
                }} noValidate>
                    <FormErrorSummary title={t('auth.formErrorTitle')} errors={errors} />
                    <Field id="current-password" label={t('auth.currentPassword')} required error={form.errors.current_password}>
                        <TextInput id="current-password" type="password" value={form.data.current_password} onChange={(event) => form.setData('current_password', event.target.value)} autoComplete="current-password" required invalid={Boolean(form.errors.current_password)} />
                    </Field>
                    <Field id="new-password" label={t('auth.newPassword')} required help={t('auth.passwordHelp')} error={form.errors.password}>
                        <TextInput id="new-password" type="password" value={form.data.password} onChange={(event) => form.setData('password', event.target.value)} autoComplete="new-password" required invalid={Boolean(form.errors.password)} />
                    </Field>
                    <Field id="password-confirmation" label={t('auth.passwordConfirmation')} required error={form.errors.password_confirmation}>
                        <TextInput id="password-confirmation" type="password" value={form.data.password_confirmation} onChange={(event) => form.setData('password_confirmation', event.target.value)} autoComplete="new-password" required invalid={Boolean(form.errors.password_confirmation)} />
                    </Field>
                    <div className="flex justify-end">
                        <Button type="submit" loading={form.processing} loadingLabel={t('settings.password.submitting')}>
                            {t('settings.password.submit')}
                        </Button>
                    </div>
                </form>
            </main>
        </AuthenticatedLayout>
    );
}

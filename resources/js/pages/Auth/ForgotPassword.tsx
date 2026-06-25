import { Head, Link, useForm } from '@inertiajs/react';
import { AuthPanel } from '../../components/auth/AuthPanel';
import { Field } from '../../components/forms/Field';
import { FormErrorSummary } from '../../components/forms/FormErrorSummary';
import { TextInput } from '../../components/forms/controls';
import { Button } from '../../components/ui/Button';
import { useLocale } from '../../hooks/use-locale';
import { GuestLayout } from '../../layouts/GuestLayout';

type ForgotPasswordForm = {
    email: string;
};

export default function ForgotPassword() {
    const { t } = useLocale();
    const form = useForm<ForgotPasswordForm>({ email: '' });
    const errors = form.errors.email ? [{ fieldId: 'forgot-email', message: form.errors.email }] : [];

    return (
        <GuestLayout>
            <Head title={t('auth.forgot.title')} />
            <AuthPanel
                title={t('auth.forgot.title')}
                description={t('auth.forgot.description')}
                footer={<Link href="/login" className="font-semibold text-accent-strong underline underline-offset-4">{t('auth.backToLogin')}</Link>}
            >
                <form className="space-y-5" onSubmit={(event) => {
                    event.preventDefault();
                    form.post('/forgot-password');
                }} noValidate>
                    <FormErrorSummary title={t('auth.formErrorTitle')} errors={errors} />
                    <Field id="forgot-email" label={t('auth.email')} required error={form.errors.email}>
                        <TextInput id="forgot-email" name="email" type="email" value={form.data.email} onChange={(event) => form.setData('email', event.target.value)} autoComplete="email" inputMode="email" required invalid={Boolean(form.errors.email)} />
                    </Field>
                    <Button type="submit" loading={form.processing} loadingLabel={t('auth.forgot.submitting')} className="w-full">{t('auth.forgot.submit')}</Button>
                </form>
            </AuthPanel>
        </GuestLayout>
    );
}

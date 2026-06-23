import { Head, Link, useForm } from '@inertiajs/react';
import { AuthPanel } from '../../components/auth/AuthPanel';
import { Field } from '../../components/forms/Field';
import { FormErrorSummary } from '../../components/forms/FormErrorSummary';
import { TextInput } from '../../components/forms/controls';
import { Button } from '../../components/ui/Button';
import { useLocale } from '../../hooks/use-locale';
import { GuestLayout } from '../../layouts/GuestLayout';

type ResetPasswordProps = {
    token: string;
    email: string;
};

type ResetPasswordForm = {
    token: string;
    email: string;
    password: string;
    password_confirmation: string;
};

export default function ResetPassword({ token, email }: ResetPasswordProps) {
    const { t } = useLocale();
    const form = useForm<ResetPasswordForm>({
        token,
        email,
        password: '',
        password_confirmation: '',
    });
    const errors = [
        form.errors.email ? { fieldId: 'reset-email', message: form.errors.email } : null,
        form.errors.password ? { fieldId: 'reset-password', message: form.errors.password } : null,
        form.errors.password_confirmation ? { fieldId: 'reset-password-confirmation', message: form.errors.password_confirmation } : null,
        form.errors.token ? { fieldId: 'reset-password', message: form.errors.token } : null,
    ].filter((error): error is { fieldId: string; message: string } => error !== null);

    return (
        <GuestLayout>
            <Head title={t('auth.reset.title')} />
            <AuthPanel
                title={t('auth.reset.title')}
                description={t('auth.reset.description')}
                footer={<Link href="/login" className="font-semibold text-accent-strong underline underline-offset-4">{t('auth.backToLogin')}</Link>}
            >
                <form className="space-y-5" onSubmit={(event) => {
                    event.preventDefault();
                    form.post('/reset-password', {
                        onError: () => form.reset('password', 'password_confirmation'),
                    });
                }} noValidate>
                    <FormErrorSummary title={t('auth.formErrorTitle')} errors={errors} />
                    <Field id="reset-email" label={t('auth.email')} required error={form.errors.email}>
                        <TextInput id="reset-email" name="email" type="email" value={form.data.email} onChange={(event) => form.setData('email', event.target.value)} autoComplete="email" inputMode="email" required invalid={Boolean(form.errors.email)} />
                    </Field>
                    <Field id="reset-password" label={t('auth.newPassword')} required help={t('auth.passwordHelp')} error={form.errors.password ?? form.errors.token}>
                        <TextInput id="reset-password" name="password" type="password" value={form.data.password} onChange={(event) => form.setData('password', event.target.value)} autoComplete="new-password" required invalid={Boolean(form.errors.password ?? form.errors.token)} />
                    </Field>
                    <Field id="reset-password-confirmation" label={t('auth.passwordConfirmation')} required error={form.errors.password_confirmation}>
                        <TextInput id="reset-password-confirmation" name="password_confirmation" type="password" value={form.data.password_confirmation} onChange={(event) => form.setData('password_confirmation', event.target.value)} autoComplete="new-password" required invalid={Boolean(form.errors.password_confirmation)} />
                    </Field>
                    <Button type="submit" loading={form.processing} loadingLabel={t('auth.reset.submitting')} className="w-full">{t('auth.reset.submit')}</Button>
                </form>
            </AuthPanel>
        </GuestLayout>
    );
}

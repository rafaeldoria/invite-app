import { Head, Link, useForm } from '@inertiajs/react';
import { AuthPanel } from '../../components/auth/AuthPanel';
import { Field } from '../../components/forms/Field';
import { FormErrorSummary } from '../../components/forms/FormErrorSummary';
import { TextInput } from '../../components/forms/controls';
import { Button } from '../../components/ui/Button';
import { useLocale } from '../../hooks/use-locale';
import { GuestLayout } from '../../layouts/GuestLayout';

type RegisterForm = {
    name: string;
    email: string;
    password: string;
    password_confirmation: string;
};

export default function Register() {
    const { t } = useLocale();
    const form = useForm<RegisterForm>({
        name: '',
        email: '',
        password: '',
        password_confirmation: '',
    });
    const errors = [
        form.errors.name ? { fieldId: 'register-name', message: form.errors.name } : null,
        form.errors.email ? { fieldId: 'register-email', message: form.errors.email } : null,
        form.errors.password ? { fieldId: 'register-password', message: form.errors.password } : null,
        form.errors.password_confirmation ? { fieldId: 'register-password-confirmation', message: form.errors.password_confirmation } : null,
    ].filter((error): error is { fieldId: string; message: string } => error !== null);

    return (
        <GuestLayout>
            <Head title={t('auth.register.title')} />
            <AuthPanel
                title={t('auth.register.title')}
                description={t('auth.register.description')}
                footer={<p>{t('auth.register.hasAccount')} <Link href="/login" className="font-semibold text-accent-strong underline underline-offset-4">{t('auth.register.signIn')}</Link></p>}
            >
                <form className="space-y-5" onSubmit={(event) => {
                    event.preventDefault();
                    form.post('/register', {
                        onError: () => form.reset('password', 'password_confirmation'),
                    });
                }} noValidate>
                    <FormErrorSummary title={t('auth.formErrorTitle')} errors={errors} />
                    <Field id="register-name" label={t('auth.name')} required error={form.errors.name}>
                        <TextInput id="register-name" name="name" value={form.data.name} onChange={(event) => form.setData('name', event.target.value)} autoComplete="name" required invalid={Boolean(form.errors.name)} />
                    </Field>
                    <Field id="register-email" label={t('auth.email')} required error={form.errors.email}>
                        <TextInput id="register-email" name="email" type="email" value={form.data.email} onChange={(event) => form.setData('email', event.target.value)} autoComplete="email" inputMode="email" required invalid={Boolean(form.errors.email)} />
                    </Field>
                    <Field id="register-password" label={t('auth.password')} required help={t('auth.passwordHelp')} error={form.errors.password}>
                        <TextInput id="register-password" name="password" type="password" value={form.data.password} onChange={(event) => form.setData('password', event.target.value)} autoComplete="new-password" required invalid={Boolean(form.errors.password)} />
                    </Field>
                    <Field id="register-password-confirmation" label={t('auth.passwordConfirmation')} required error={form.errors.password_confirmation}>
                        <TextInput id="register-password-confirmation" name="password_confirmation" type="password" value={form.data.password_confirmation} onChange={(event) => form.setData('password_confirmation', event.target.value)} autoComplete="new-password" required invalid={Boolean(form.errors.password_confirmation)} />
                    </Field>
                    <Button type="submit" loading={form.processing} loadingLabel={t('auth.register.submitting')} className="w-full">{t('auth.register.submit')}</Button>
                </form>
            </AuthPanel>
        </GuestLayout>
    );
}

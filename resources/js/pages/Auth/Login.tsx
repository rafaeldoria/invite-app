import { Head, Link, useForm } from '@inertiajs/react';
import { AuthPanel } from '../../components/auth/AuthPanel';
import { Field } from '../../components/forms/Field';
import { FormErrorSummary } from '../../components/forms/FormErrorSummary';
import { Checkbox, TextInput } from '../../components/forms/controls';
import { Button } from '../../components/ui/Button';
import { useLocale } from '../../hooks/use-locale';
import { GuestLayout } from '../../layouts/GuestLayout';

type LoginForm = {
    email: string;
    password: string;
    remember: boolean;
};

export default function Login() {
    const { t } = useLocale();
    const form = useForm<LoginForm>({
        email: '',
        password: '',
        remember: false,
    });
    const errors = [
        form.errors.email ? { fieldId: 'login-email', message: form.errors.email } : null,
        form.errors.password ? { fieldId: 'login-password', message: form.errors.password } : null,
    ].filter((error): error is { fieldId: string; message: string } => error !== null);

    return (
        <GuestLayout>
            <Head title={t('auth.login.title')} />
            <AuthPanel
                title={t('auth.login.title')}
                description={t('auth.login.description')}
                footer={<p>{t('auth.login.noAccount')} <Link href="/register" className="font-semibold text-accent-strong underline underline-offset-4">{t('auth.login.createAccount')}</Link></p>}
            >
                <form className="space-y-5" onSubmit={(event) => {
                    event.preventDefault();
                    form.post('/login', {
                        onError: () => form.reset('password'),
                    });
                }} noValidate>
                    <FormErrorSummary title={t('auth.formErrorTitle')} errors={errors} />
                    <Field id="login-email" label={t('auth.email')} required error={form.errors.email}>
                        <TextInput id="login-email" name="email" type="email" value={form.data.email} onChange={(event) => form.setData('email', event.target.value)} autoComplete="email" inputMode="email" required invalid={Boolean(form.errors.email)} />
                    </Field>
                    <Field id="login-password" label={t('auth.password')} required error={form.errors.password}>
                        <TextInput id="login-password" name="password" type="password" value={form.data.password} onChange={(event) => form.setData('password', event.target.value)} autoComplete="current-password" required invalid={Boolean(form.errors.password)} />
                    </Field>
                    <div className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                        <Checkbox name="remember" label={t('auth.login.remember')} checked={form.data.remember} onChange={(event) => form.setData('remember', event.target.checked)} />
                        <Link href="/forgot-password" className="rounded-md text-sm font-semibold text-accent-strong underline underline-offset-4 focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-focus">{t('auth.login.forgotPassword')}</Link>
                    </div>
                    <Button type="submit" loading={form.processing} loadingLabel={t('auth.login.submitting')} className="w-full">{t('auth.login.submit')}</Button>
                </form>
            </AuthPanel>
        </GuestLayout>
    );
}

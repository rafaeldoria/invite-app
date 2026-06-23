import { Head, router } from '@inertiajs/react';
import { AuthPanel } from '../../components/auth/AuthPanel';
import { Button } from '../../components/ui/Button';
import { useLocale } from '../../hooks/use-locale';
import { AuthenticatedLayout } from '../../layouts/AuthenticatedLayout';

export default function VerifyEmail() {
    const { t } = useLocale();

    return (
        <AuthenticatedLayout>
            <Head title={t('auth.verify.title')} />
            <AuthPanel title={t('auth.verify.title')} description={t('auth.verify.description')}>
                <div className="space-y-4">
                    <p className="text-sm leading-6 text-muted">{t('auth.verify.help')}</p>
                    <div className="flex flex-col gap-3 sm:flex-row">
                        <Button type="button" className="flex-1" onClick={() => router.post('/email/verification-notification', {}, { preserveScroll: true })}>{t('auth.verify.resend')}</Button>
                        <Button type="button" variant="secondary" className="flex-1" onClick={() => router.post('/logout')}>{t('auth.logout')}</Button>
                    </div>
                </div>
            </AuthPanel>
        </AuthenticatedLayout>
    );
}

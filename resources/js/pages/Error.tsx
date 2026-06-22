import { Head } from '@inertiajs/react';
import { PageError } from '../components/feedback/PageError';
import { useLocale } from '../hooks/use-locale';
import { GuestLayout } from '../layouts/GuestLayout';
import type { TranslationKey } from '../utils/translations';

const supportedStatuses = [403, 404, 419, 429, 500, 503] as const;
type SupportedStatus = (typeof supportedStatuses)[number];

function normalizedStatus(status: number): SupportedStatus {
    return supportedStatuses.includes(status as SupportedStatus) ? (status as SupportedStatus) : 500;
}

export default function Error({ status }: { status: number }) {
    const { t } = useLocale();
    const safeStatus = normalizedStatus(status);
    const titleKey = `error.${safeStatus}.title` as TranslationKey;
    const descriptionKey = `error.${safeStatus}.description` as TranslationKey;

    return (
        <GuestLayout>
            <Head title={t(titleKey)} />
            <PageError status={safeStatus} title={t(titleKey)} description={t(descriptionKey)} actionLabel={t('error.action')} />
        </GuestLayout>
    );
}

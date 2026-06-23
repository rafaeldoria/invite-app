import { Link, usePage } from '@inertiajs/react';
import type { ReactNode } from 'react';
import { FlashNotice } from '../components/feedback/FlashNotice';
import { Preferences } from '../components/navigation/Preferences';
import { useLocale } from '../hooks/use-locale';
import type { SharedPageProps } from '../types/shared';

export function GuestLayout({ children }: { children: ReactNode }) {
    const { app, flash } = usePage<SharedPageProps>().props;
    const { locale, setLocale, isChanging, t } = useLocale();
    return <div className="min-h-screen bg-canvas text-ink"><a href="#main-content" className="fixed left-4 top-4 z-tooltip -translate-y-24 rounded-lg bg-ink px-4 py-3 text-canvas focus:translate-y-0">{t('app.skipContent')}</a><header className="mx-auto flex min-h-16 max-w-6xl flex-wrap items-center justify-between gap-x-4 gap-y-2 px-5 py-2"><Link href="/" className="rounded-md text-lg font-bold focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-focus">{app.name}</Link><Preferences locale={locale} setLocale={setLocale} isChanging={isChanging} t={t} /></header><FlashNotice success={flash.success} error={flash.error} dismissLabel={t('flash.dismiss')} />{children}</div>;
}

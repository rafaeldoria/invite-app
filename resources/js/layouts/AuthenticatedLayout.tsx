import { Link, usePage } from '@inertiajs/react';
import { useState, type ReactNode } from 'react';
import { FlashNotice } from '../components/feedback/FlashNotice';
import { MobileNavigation, NavigationLinks } from '../components/navigation/AppNavigation';
import { Preferences } from '../components/navigation/Preferences';
import { useLocale } from '../hooks/use-locale';
import type { NavigationItem, SharedPageProps } from '../types/shared';

export function AuthenticatedLayout({ children, navigation = [] }: { children: ReactNode; navigation?: NavigationItem[] }) {
    const { app, flash } = usePage<SharedPageProps>().props;
    const { locale, setLocale, t } = useLocale();
    const [menuOpen, setMenuOpen] = useState(false);
    const items = [{ href: '/', label: t('app.home') }, ...navigation];

    return (
        <div className="min-h-screen bg-canvas text-ink">
            <a href="#main-content" className="fixed left-4 top-4 z-tooltip -translate-y-24 rounded-lg bg-ink px-4 py-3 text-canvas focus:translate-y-0">{t('app.skipContent')}</a>
            <header className="sticky top-0 z-sticky border-b border-border bg-canvas/95"><div className="mx-auto flex min-h-16 max-w-7xl items-center gap-5 px-5"><Link href="/" className="mr-auto rounded-md text-lg font-bold tracking-[-0.02em] focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-focus">{app.name}</Link><div className="hidden items-center gap-4 md:flex"><NavigationLinks items={items} ariaLabel={t('app.navigation')} /><Preferences locale={locale} setLocale={setLocale} t={t} /></div><button type="button" onClick={() => setMenuOpen(true)} className="flex min-h-11 items-center rounded-lg border border-border px-3 text-sm font-semibold focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-focus md:hidden" aria-haspopup="dialog" aria-expanded={menuOpen}>{t('app.menu')}</button></div></header>
            <MobileNavigation open={menuOpen} onClose={() => setMenuOpen(false)} items={items} title={t('app.navigation')} closeLabel={t('app.closeMenu')}><p className="mb-3 text-sm font-semibold text-muted">{t('app.preferences')}</p><Preferences locale={locale} setLocale={setLocale} t={t} /></MobileNavigation>
            <FlashNotice success={flash.success} error={flash.error} dismissLabel={t('flash.dismiss')} />
            {children}
        </div>
    );
}

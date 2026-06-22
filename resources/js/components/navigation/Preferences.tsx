import { useId } from 'react';
import type { Locale } from '../../types/shared';
import type { TranslationKey } from '../../utils/translations';
import { useTheme, type ThemeMode } from '../../hooks/use-theme';

export function Preferences({ locale, setLocale, t }: { locale: Locale; setLocale: (locale: Locale) => void; t: (key: TranslationKey) => string }) {
    const { mode, setMode } = useTheme();
    const localeId = useId();
    const themeId = useId();

    return (
        <div className="flex flex-wrap items-center gap-3">
            <label className="sr-only" htmlFor={localeId}>{t('locale.label')}</label>
            <select id={localeId} value={locale} onChange={(event) => setLocale(event.target.value as Locale)} className="min-h-11 rounded-lg border border-border bg-surface px-3 text-sm font-medium text-ink focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-focus">
                <option value="pt-BR">{t('locale.pt-BR')}</option>
                <option value="en-US">{t('locale.en-US')}</option>
            </select>
            <label className="sr-only" htmlFor={themeId}>{t('theme.label')}</label>
            <select id={themeId} value={mode} onChange={(event) => setMode(event.target.value as ThemeMode)} className="min-h-11 rounded-lg border border-border bg-surface px-3 text-sm font-medium text-ink focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-focus">
                <option value="system">{t('theme.system')}</option>
                <option value="light">{t('theme.light')}</option>
                <option value="dark">{t('theme.dark')}</option>
            </select>
        </div>
    );
}

import type { TranslationKey } from '../../locales';
import type { Locale } from '../../types/shared';
import { useTheme, type ThemeMode } from '../../hooks/use-theme';

export function Preferences({ locale, setLocale, isChanging = false, t }: { locale: Locale; setLocale: (locale: Locale) => void; isChanging?: boolean; t: (key: TranslationKey) => string }) {
    const { mode, setMode } = useTheme();
    const localeOptions: Array<{ value: Locale; icon: string; label: string }> = [
        { value: 'pt-BR', icon: '🇧🇷', label: t('locale.pt-BR') },
        { value: 'en-US', icon: '🇺🇸', label: t('locale.en-US') },
    ];
    const themeOptions: Array<{ value: ThemeMode; icon: string; label: string }> = [
        { value: 'light', icon: '☀️', label: t('theme.light') },
        { value: 'dark', icon: '🌙', label: t('theme.dark') },
        { value: 'system', icon: '💻', label: t('theme.system') },
    ];

    return (
        <div className="flex flex-wrap items-center gap-2">
            <div className="flex items-center rounded-lg border border-border bg-surface p-1" role="group" aria-label={t('locale.label')} aria-busy={isChanging || undefined}>
                {localeOptions.map((option) => (
                    <PreferenceButton
                        key={option.value}
                        icon={option.icon}
                        label={option.label}
                        selected={locale === option.value}
                        disabled={isChanging}
                        onClick={() => setLocale(option.value)}
                    />
                ))}
            </div>
            {isChanging ? <span className="sr-only" role="status">{t('locale.updating')}</span> : null}
            <div className="flex items-center rounded-lg border border-border bg-surface p-1" role="group" aria-label={t('theme.label')}>
                {themeOptions.map((option) => (
                    <PreferenceButton
                        key={option.value}
                        icon={option.icon}
                        label={option.label}
                        selected={mode === option.value}
                        onClick={() => setMode(option.value)}
                    />
                ))}
            </div>
        </div>
    );
}

function PreferenceButton({ icon, label, selected, disabled = false, onClick }: { icon: string; label: string; selected: boolean; disabled?: boolean; onClick: () => void }) {
    return (
        <button
            type="button"
            title={label}
            aria-label={label}
            aria-pressed={selected}
            disabled={disabled}
            onClick={onClick}
            className={`flex size-10 items-center justify-center rounded-md text-lg transition-colors focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-focus disabled:cursor-wait disabled:opacity-60 ${selected ? 'bg-accent-soft text-accent-strong' : 'text-muted hover:bg-surface-muted hover:text-ink'}`}
        >
            <span aria-hidden="true">{icon}</span>
        </button>
    );
}

import { Link, router } from '@inertiajs/react';
import { useEffect, useRef, useState } from 'react';
import type { TranslationKey } from '../../locales';
import type { Locale } from '../../types/shared';
import { useTheme, type ThemeMode } from '../../hooks/use-theme';

type SettingsMenuProps = {
    locale: Locale;
    setLocale: (locale: Locale) => void;
    isChanging?: boolean;
    t: (key: TranslationKey) => string;
};

export function SettingsMenu({ locale, setLocale, isChanging = false, t }: SettingsMenuProps) {
    const { mode, setMode } = useTheme();
    const [open, setOpen] = useState(false);
    const [panel, setPanel] = useState<'language' | 'theme' | null>(null);
    const ref = useRef<HTMLDivElement>(null);
    const localeOptions: Array<{ value: Locale; icon: string; label: string }> = [
        { value: 'pt-BR', icon: '🇧🇷', label: t('locale.pt-BR') },
        { value: 'en-US', icon: '🇺🇸', label: t('locale.en-US') },
    ];
    const themeOptions: Array<{ value: ThemeMode; icon: string; label: string }> = [
        { value: 'light', icon: '☀️', label: t('theme.light') },
        { value: 'dark', icon: '🌙', label: t('theme.dark') },
        { value: 'system', icon: '💻', label: t('theme.system') },
    ];

    useEffect(() => {
        if (!open) return;

        function closeOnOutsideClick(event: MouseEvent) {
            if (event.target instanceof Node && !ref.current?.contains(event.target)) {
                setOpen(false);
                setPanel(null);
            }
        }

        function closeOnEscape(event: KeyboardEvent) {
            if (event.key === 'Escape') {
                setOpen(false);
                setPanel(null);
            }
        }

        document.addEventListener('mousedown', closeOnOutsideClick);
        document.addEventListener('keydown', closeOnEscape);

        return () => {
            document.removeEventListener('mousedown', closeOnOutsideClick);
            document.removeEventListener('keydown', closeOnEscape);
        };
    }, [open]);

    function togglePanel(nextPanel: 'language' | 'theme') {
        setPanel((current) => current === nextPanel ? null : nextPanel);
    }

    function logout() {
        setOpen(false);
        setPanel(null);
        router.post('/logout');
    }

    return (
        <div ref={ref} className="relative">
            <button
                type="button"
                onClick={() => {
                    setOpen((current) => !current);
                    setPanel(null);
                }}
                className={`flex size-11 items-center justify-center rounded-lg bg-surface text-xl transition-colors hover:bg-surface-muted focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-focus ${open ? 'bg-accent-soft text-accent-strong' : 'text-ink'}`}
                aria-label={t('settings.menu')}
                aria-haspopup="menu"
                aria-expanded={open}
                title={t('settings.menu')}
            >
                <span aria-hidden="true">⚙️</span>
            </button>

            {open ? (
                <div role="menu" className="absolute right-0 top-12 z-tooltip w-72 rounded-lg border border-border bg-surface p-2 text-sm shadow-lg">
                    <Link
                        href="/settings/password"
                        role="menuitem"
                        className="flex min-h-11 items-center gap-3 rounded-md px-3 font-semibold text-ink hover:bg-surface-muted focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-focus"
                        onClick={() => setOpen(false)}
                    >
                        <span aria-hidden="true">🔑</span>
                        {t('settings.changePassword')}
                    </Link>

                    <MenuButton icon="🎨" label={t('settings.theme')} expanded={panel === 'theme'} onClick={() => togglePanel('theme')} />
                    {panel === 'theme' ? (
                        <div className="mb-1 flex items-center gap-1 rounded-md bg-canvas p-1" role="group" aria-label={t('theme.label')}>
                            {themeOptions.map((option) => (
                                <PreferenceIconButton
                                    key={option.value}
                                    icon={option.icon}
                                    label={option.label}
                                    selected={mode === option.value}
                                    onClick={() => setMode(option.value)}
                                />
                            ))}
                        </div>
                    ) : null}

                    <MenuButton icon="🌐" label={t('settings.language')} expanded={panel === 'language'} onClick={() => togglePanel('language')} />
                    {panel === 'language' ? (
                        <div className="mb-1 flex items-center gap-1 rounded-md bg-canvas p-1" role="group" aria-label={t('locale.label')} aria-busy={isChanging || undefined}>
                            {localeOptions.map((option) => (
                                <PreferenceIconButton
                                    key={option.value}
                                    icon={option.icon}
                                    label={option.label}
                                    selected={locale === option.value}
                                    disabled={isChanging}
                                    onClick={() => setLocale(option.value)}
                                />
                            ))}
                            {isChanging ? <span className="sr-only" role="status">{t('locale.updating')}</span> : null}
                        </div>
                    ) : null}

                    <button
                        type="button"
                        role="menuitem"
                        onClick={logout}
                        className="mt-1 flex min-h-11 w-full items-center gap-3 rounded-md px-3 text-left font-semibold text-danger hover:bg-danger-soft focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-focus"
                    >
                        <span aria-hidden="true">↪️</span>
                        {t('auth.logout')}
                    </button>
                </div>
            ) : null}
        </div>
    );
}

function MenuButton({ icon, label, expanded, onClick }: { icon: string; label: string; expanded: boolean; onClick: () => void }) {
    return (
        <button
            type="button"
            role="menuitem"
            aria-expanded={expanded}
            onClick={onClick}
            className="flex min-h-11 w-full items-center justify-between gap-3 rounded-md px-3 text-left font-semibold text-ink hover:bg-surface-muted focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-focus"
        >
            <span className="flex items-center gap-3">
                <span aria-hidden="true">{icon}</span>
                {label}
            </span>
            <span aria-hidden="true" className="text-muted">{expanded ? '−' : '+'}</span>
        </button>
    );
}

function PreferenceIconButton({ icon, label, selected, disabled = false, onClick }: { icon: string; label: string; selected: boolean; disabled?: boolean; onClick: () => void }) {
    return (
        <button
            type="button"
            title={label}
            aria-label={label}
            aria-pressed={selected}
            disabled={disabled}
            onClick={onClick}
            className={`flex size-10 flex-1 items-center justify-center rounded-md text-lg transition-colors focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-focus disabled:cursor-wait disabled:opacity-60 ${selected ? 'bg-accent-soft text-accent-strong' : 'text-muted hover:bg-surface-muted hover:text-ink'}`}
        >
            <span aria-hidden="true">{icon}</span>
        </button>
    );
}

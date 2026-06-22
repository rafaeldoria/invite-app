import { useCallback, useEffect, useState } from 'react';
import { readStorage, writeStorage } from '../utils/storage';

export type ThemeMode = 'system' | 'light' | 'dark';

const storageKey = 'invite-app-theme';

function isThemeMode(value: string | null): value is ThemeMode {
    return value === 'system' || value === 'light' || value === 'dark';
}

function storedMode(): ThemeMode {
    const value = readStorage(storageKey);

    return isThemeMode(value) ? value : 'system';
}

function resolvedTheme(mode: ThemeMode): 'light' | 'dark' {
    return mode === 'system' ? (window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light') : mode;
}

export function useTheme() {
    const [mode, setMode] = useState<ThemeMode>(() => storedMode());

    const apply = useCallback((nextMode: ThemeMode) => {
        document.documentElement.dataset.theme = resolvedTheme(nextMode);
        document.documentElement.style.colorScheme = resolvedTheme(nextMode);
    }, []);

    useEffect(() => {
        apply(mode);
        writeStorage(storageKey, mode);

        const media = window.matchMedia('(prefers-color-scheme: dark)');
        const handleChange = () => mode === 'system' && apply(mode);
        media.addEventListener('change', handleChange);

        return () => media.removeEventListener('change', handleChange);
    }, [apply, mode]);

    useEffect(() => {
        const synchronize = (event: Event) => setMode((event as CustomEvent<ThemeMode>).detail);
        window.addEventListener('invite-app:theme-change', synchronize);

        return () => window.removeEventListener('invite-app:theme-change', synchronize);
    }, []);

    const changeMode = useCallback((nextMode: ThemeMode) => {
        setMode(nextMode);
        window.dispatchEvent(new CustomEvent<ThemeMode>('invite-app:theme-change', { detail: nextMode }));
    }, []);

    return { mode, setMode: changeMode };
}

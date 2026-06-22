import { usePage } from '@inertiajs/react';
import { useCallback, useEffect, useState } from 'react';
import type { Locale, SharedPageProps } from '../types/shared';
import { readStorage, writeStorage } from '../utils/storage';
import { translate } from '../utils/translations';

const storageKey = 'invite-app-locale';

function isLocale(value: string | null): value is Locale {
    return value === 'pt-BR' || value === 'en-US';
}

export function useLocale() {
    const pageLocale = usePage<SharedPageProps>().props.locale;
    const [locale, setLocale] = useState<Locale>(() => {
        const storedLocale = readStorage(storageKey);

        return isLocale(storedLocale) ? storedLocale : pageLocale;
    });

    useEffect(() => {
        const synchronize = (event: Event) => setLocale((event as CustomEvent<Locale>).detail);
        window.addEventListener('invite-app:locale-change', synchronize);

        return () => window.removeEventListener('invite-app:locale-change', synchronize);
    }, []);

    useEffect(() => {
        document.documentElement.lang = locale;
    }, [locale]);

    const changeLocale = useCallback((nextLocale: Locale) => {
        setLocale(nextLocale);
        writeStorage(storageKey, nextLocale);
        window.dispatchEvent(new CustomEvent<Locale>('invite-app:locale-change', { detail: nextLocale }));
    }, []);

    return {
        locale,
        setLocale: changeLocale,
        t: (key: Parameters<typeof translate>[1]) => translate(locale, key),
    };
}

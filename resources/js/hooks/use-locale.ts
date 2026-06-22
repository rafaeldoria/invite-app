import { usePage } from '@inertiajs/react';
import { useCallback, useEffect, useState } from 'react';
import type { Locale, SharedPageProps } from '../types/shared';
import { translate } from '../utils/translations';

export function useLocale() {
    const pageLocale = usePage<SharedPageProps>().props.locale;
    const [locale, setLocale] = useState<Locale>(pageLocale);

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
        window.dispatchEvent(new CustomEvent<Locale>('invite-app:locale-change', { detail: nextLocale }));
    }, []);

    return {
        locale,
        setLocale: changeLocale,
        t: (key: Parameters<typeof translate>[1]) => translate(locale, key),
    };
}

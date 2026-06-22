import { router, usePage } from '@inertiajs/react';
import { useCallback, useEffect, useState } from 'react';
import { translate, translatePlural, type Replacements, type TranslationKey } from '../locales';
import type { Locale, SharedPageProps } from '../types/shared';

export function useLocale() {
    const locale = usePage<SharedPageProps>().props.locale;
    const [isChanging, setIsChanging] = useState(false);

    useEffect(() => {
        document.documentElement.lang = locale;
    }, [locale]);

    const changeLocale = useCallback((nextLocale: Locale) => {
        if (nextLocale === locale) return;

        router.patch('/locale', { locale: nextLocale }, {
            preserveScroll: true,
            preserveState: true,
            onStart: () => setIsChanging(true),
            onFinish: () => setIsChanging(false),
        });
    }, [locale]);

    return {
        locale,
        isChanging,
        setLocale: changeLocale,
        t: (key: TranslationKey, replacements?: Replacements) => translate(locale, key, replacements),
        tp: (key: Parameters<typeof translatePlural>[1], count: number, replacements?: Replacements) =>
            translatePlural(locale, key, count, replacements),
    };
}

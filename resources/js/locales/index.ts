import type { Locale } from '../types/shared';
import { enUS, type TranslationKey } from './en-US.ts';
import { ptBR } from './pt-BR.ts';

type Replacements = Record<string, string | number>;

type PluralKey = {
    [Key in TranslationKey]: Key extends `${infer Base}.one`
        ? `${Base}.other` extends TranslationKey
            ? Base
            : never
        : never;
}[TranslationKey];

const catalogs = {
    'pt-BR': ptBR,
    'en-US': enUS,
};

function interpolate(message: string, replacements: Replacements): string {
    return message.replace(/\{([a-zA-Z][a-zA-Z0-9_]*)\}/g, (placeholder, variable: string) => {
        if (Object.hasOwn(replacements, variable)) {
            return String(replacements[variable]);
        }

        if (import.meta.env?.DEV === true) {
            console.error(`Missing translation variable: ${variable}`);
        }

        return `[missing:${variable}]`;
    });
}

export function translate(locale: Locale, key: TranslationKey, replacements: Replacements = {}): string {
    const message = catalogs[locale]?.[key] ?? enUS[key];

    if (message === undefined) {
        if (import.meta.env?.DEV === true) {
            console.error(`Missing translation key: ${key}`);
        }

        return `[${key}]`;
    }

    return interpolate(message, replacements);
}

export function translatePlural(locale: Locale, key: PluralKey, count: number, replacements: Replacements = {}): string {
    const form = count !== 0 && new Intl.PluralRules(locale).select(count) === 'one' ? 'one' : 'other';

    return translate(locale, `${key}.${form}` as TranslationKey, { ...replacements, count });
}

export type { PluralKey, Replacements, TranslationKey };

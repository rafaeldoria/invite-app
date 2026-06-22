import type { Locale } from '../types/shared';

export function formatDate(value: Date | string | number, locale: Locale, timeZone: string, options: Intl.DateTimeFormatOptions = {}): string {
    return new Intl.DateTimeFormat(locale, { dateStyle: 'long', ...options, timeZone }).format(new Date(value));
}

export function formatTime(value: Date | string | number, locale: Locale, timeZone: string, options: Intl.DateTimeFormatOptions = {}): string {
    return new Intl.DateTimeFormat(locale, { timeStyle: 'short', ...options, timeZone }).format(new Date(value));
}

export function formatNumber(value: number, locale: Locale, options: Intl.NumberFormatOptions = {}): string {
    return new Intl.NumberFormat(locale, options).format(value);
}

export function selectPlural(value: number, locale: Locale): Intl.LDMLPluralRule {
    return new Intl.PluralRules(locale).select(value);
}

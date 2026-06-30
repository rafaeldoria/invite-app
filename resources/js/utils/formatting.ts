import type { Locale } from '../types/shared';

export function formatDate(value: Date | string | number, locale: Locale, timeZone: string, options: Intl.DateTimeFormatOptions = {}): string {
    return new Intl.DateTimeFormat(locale, { dateStyle: 'long', ...options, timeZone }).format(new Date(value));
}

export function formatTime(value: Date | string | number, locale: Locale, timeZone: string, options: Intl.DateTimeFormatOptions = {}): string {
    return new Intl.DateTimeFormat(locale, { timeStyle: 'short', ...options, timeZone }).format(new Date(value));
}

export function formatFormDate(value: string, locale: Locale): string {
    const date = parseIsoDate(value);

    if (date === null) {
        return value;
    }

    const { year, month, day } = date;

    return locale === 'pt-BR'
        ? `${day}/${month}/${year}`
        : `${month}/${day}/${year}`;
}

export function parseFormDateInput(value: string, locale: Locale): string | null {
    const input = value.trim();

    if (input === '') {
        return '';
    }

    const isoDate = parseIsoDate(input);

    if (isoDate !== null) {
        return `${isoDate.year}-${isoDate.month}-${isoDate.day}`;
    }

    const localizedDate = input.match(/^(\d{1,2})[./-](\d{1,2})[./-](\d{4})$/);

    if (localizedDate === null) {
        return null;
    }

    const first = Number(localizedDate[1]);
    const second = Number(localizedDate[2]);
    const year = Number(localizedDate[3]);
    const month = locale === 'pt-BR' ? second : first;
    const day = locale === 'pt-BR' ? first : second;

    if (! isValidDate(year, month, day)) {
        return null;
    }

    return `${String(year).padStart(4, '0')}-${String(month).padStart(2, '0')}-${String(day).padStart(2, '0')}`;
}

export function formatFormTime(value: string, locale: Locale): string {
    const time = parseTwentyFourHourTime(value);

    if (time === null) {
        return value;
    }

    if (locale === 'pt-BR') {
        return `${time.hour}:${time.minute}`;
    }

    const hour = Number(time.hour);
    const period = hour >= 12 ? 'PM' : 'AM';
    const displayHour = hour % 12 || 12;

    return `${displayHour}:${time.minute} ${period}`;
}

export function parseFormTimeInput(value: string): string | null {
    const input = value.trim();

    if (input === '') {
        return '';
    }

    const time = parseTwentyFourHourTime(input);

    if (time !== null) {
        return `${time.hour}:${time.minute}`;
    }

    const twelveHourTime = input.match(/^(\d{1,2})(?::([0-5]\d))?\s*([ap])\.?\s*m\.?$/i);

    if (twelveHourTime === null) {
        return null;
    }

    const rawHour = Number(twelveHourTime[1]);
    const minute = twelveHourTime[2] ?? '00';
    const period = twelveHourTime[3].toLowerCase();

    if (rawHour < 1 || rawHour > 12) {
        return null;
    }

    const hour = period === 'p' ? rawHour % 12 + 12 : rawHour % 12;

    return `${String(hour).padStart(2, '0')}:${minute}`;
}

export function formatNumber(value: number, locale: Locale, options: Intl.NumberFormatOptions = {}): string {
    return new Intl.NumberFormat(locale, options).format(value);
}

export function selectPlural(value: number, locale: Locale): Intl.LDMLPluralRule {
    return new Intl.PluralRules(locale).select(value);
}

function parseIsoDate(value: string): { year: string; month: string; day: string } | null {
    const match = value.match(/^(\d{4})-(\d{2})-(\d{2})$/);

    if (match === null) {
        return null;
    }

    const year = Number(match[1]);
    const month = Number(match[2]);
    const day = Number(match[3]);

    if (! isValidDate(year, month, day)) {
        return null;
    }

    return {
        year: match[1],
        month: match[2],
        day: match[3],
    };
}

function isValidDate(year: number, month: number, day: number): boolean {
    const date = new Date(Date.UTC(year, month - 1, day));

    return date.getUTCFullYear() === year
        && date.getUTCMonth() === month - 1
        && date.getUTCDate() === day;
}

function parseTwentyFourHourTime(value: string): { hour: string; minute: string } | null {
    const match = value.match(/^([01]?\d|2[0-3]):([0-5]\d)$/);

    if (match === null) {
        return null;
    }

    return {
        hour: match[1].padStart(2, '0'),
        minute: match[2],
    };
}

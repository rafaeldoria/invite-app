import assert from 'node:assert/strict';
import test from 'node:test';
import { formatDate, formatFormDate, formatFormTime, formatNumber, formatTime, formatTimeZoneName, maskFormDateInput, maskFormTimeInput, parseFormDateInput, parseFormTimeInput, selectPlural } from './formatting.ts';

const instantNearDayBoundary = '2026-01-01T02:30:00Z';

test('date and time formatting uses the explicit event timezone', () => {
    assert.match(formatDate(instantNearDayBoundary, 'pt-BR', 'America/Sao_Paulo'), /31 de dezembro de 2025/);
    assert.match(formatDate(instantNearDayBoundary, 'en-US', 'America/Sao_Paulo'), /December 31, 2025/);
    assert.equal(formatTime(instantNearDayBoundary, 'pt-BR', 'America/Sao_Paulo'), '23:30');
    assert.equal(formatTime(instantNearDayBoundary, 'en-US', 'America/Sao_Paulo'), '11:30 PM');
});

test('timezone labels are readable and localized instead of raw IANA identifiers', () => {
    assert.match(formatTimeZoneName('2026-08-16T21:00:00Z', 'pt-BR', 'America/Sao_Paulo'), /Brasília/);
    assert.match(formatTimeZoneName('2026-08-16T21:00:00Z', 'en-US', 'America/Sao_Paulo'), /Brasilia/);
    assert.notEqual(formatTimeZoneName('2026-08-16T21:00:00Z', 'pt-BR', 'America/Sao_Paulo'), 'America/Sao_Paulo');
});

test('event form date formatting follows the selected locale', () => {
    assert.equal(formatFormDate('2026-08-16', 'pt-BR'), '16/08/2026');
    assert.equal(formatFormDate('2026-08-16', 'en-US'), '08/16/2026');
    assert.equal(maskFormDateInput('16082026'), '16/08/2026');
    assert.equal(maskFormDateInput('16a08b2026'), '16/08/2026');
    assert.equal(maskFormDateInput('1608'), '16/08');
    assert.equal(parseFormDateInput('16/08/2026', 'pt-BR'), '2026-08-16');
    assert.equal(parseFormDateInput('08/16/2026', 'en-US'), '2026-08-16');
    assert.equal(parseFormDateInput('2026-08-16', 'pt-BR'), '2026-08-16');
    assert.equal(parseFormDateInput('31/02/2026', 'pt-BR'), null);
});

test('event form time formatting follows the selected locale', () => {
    assert.equal(formatFormTime('18:00', 'pt-BR'), '18:00');
    assert.equal(formatFormTime('18:00', 'en-US'), '18:00');
    assert.equal(formatFormTime('00:05', 'en-US'), '00:05');
    assert.equal(maskFormTimeInput('1800'), '18:00');
    assert.equal(maskFormTimeInput('18h00'), '18:00');
    assert.equal(maskFormTimeInput('18'), '18');
    assert.equal(parseFormTimeInput('18:00'), '18:00');
    assert.equal(parseFormTimeInput('6:00 PM'), '18:00');
    assert.equal(parseFormTimeInput('12:05 AM'), '00:05');
    assert.equal(parseFormTimeInput('25:00'), null);
});

test('numbers and plural categories follow the canonical locale', () => {
    assert.equal(formatNumber(1234.5, 'pt-BR'), '1.234,5');
    assert.equal(formatNumber(1234.5, 'en-US'), '1,234.5');

    assert.equal(selectPlural(0, 'pt-BR'), 'one');
    assert.equal(selectPlural(0, 'en-US'), 'other');
    assert.equal(selectPlural(1, 'pt-BR'), 'one');
    assert.equal(selectPlural(1, 'en-US'), 'one');
    assert.equal(selectPlural(2, 'pt-BR'), 'other');
    assert.equal(selectPlural(2, 'en-US'), 'other');
});

import assert from 'node:assert/strict';
import test from 'node:test';
import { formatDate, formatNumber, formatTime, selectPlural } from './formatting.ts';

const instantNearDayBoundary = '2026-01-01T02:30:00Z';

test('date and time formatting uses the explicit event timezone', () => {
    assert.match(formatDate(instantNearDayBoundary, 'pt-BR', 'America/Sao_Paulo'), /31 de dezembro de 2025/);
    assert.match(formatDate(instantNearDayBoundary, 'en-US', 'America/Sao_Paulo'), /December 31, 2025/);
    assert.equal(formatTime(instantNearDayBoundary, 'pt-BR', 'America/Sao_Paulo'), '23:30');
    assert.equal(formatTime(instantNearDayBoundary, 'en-US', 'America/Sao_Paulo'), '11:30 PM');
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

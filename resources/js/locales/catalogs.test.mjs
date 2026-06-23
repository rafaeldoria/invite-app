import assert from 'node:assert/strict';
import test from 'node:test';
import { enUS } from './en-US.ts';
import { ptBR } from './pt-BR.ts';

function placeholders(message) {
    return [...message.matchAll(/\{([a-zA-Z][a-zA-Z0-9_]*)\}/g)]
        .map((match) => match[1])
        .sort();
}

test('locale catalogs have matching keys, defined values, and interpolation variables', () => {
    assert.deepEqual(Object.keys(ptBR).sort(), Object.keys(enUS).sort());

    for (const key of Object.keys(enUS)) {
        assert.equal(typeof enUS[key], 'string', `${key} must be defined in en-US`);
        assert.equal(typeof ptBR[key], 'string', `${key} must be defined in pt-BR`);
        assert.deepEqual(placeholders(ptBR[key]), placeholders(enUS[key]), `${key} must use the same variables`);
    }
});

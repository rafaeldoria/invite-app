import assert from 'node:assert/strict';
import test from 'node:test';
import { translate, translatePlural } from './index.ts';

test('translations interpolate named text values without treating them as markup', () => {
    assert.equal(translate('en-US', 'guests.count.one', { count: '<strong>1</strong>' }), '<strong>1</strong> guest');
    assert.equal(translate('pt-BR', 'guests.count.one'), '[missing:count] convidado');
});

test('limited guest and companion plural forms work in both locales', () => {
    assert.equal(translatePlural('en-US', 'guests.count', 0), '0 guests');
    assert.equal(translatePlural('en-US', 'guests.count', 1), '1 guest');
    assert.equal(translatePlural('en-US', 'guests.count', 2), '2 guests');
    assert.equal(translatePlural('pt-BR', 'companions.count', 0), '0 acompanhantes');
    assert.equal(translatePlural('pt-BR', 'companions.count', 1), '1 acompanhante');
    assert.equal(translatePlural('pt-BR', 'companions.count', 2), '2 acompanhantes');
});

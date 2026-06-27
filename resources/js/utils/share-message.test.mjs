import assert from 'node:assert/strict';
import test from 'node:test';
import { buildShareMessage, buildWhatsAppUrl } from './share-message.ts';

test('share message combines custom text, summary, and canonical URL once', () => {
    const url = 'https://invite.test/e/01HYEXAMPLE';
    const message = buildShareMessage(`Join us!\n${url}`, 'Date: June 10\nLocation: Hall', url);

    assert.equal(message, 'Join us!\n\nDate: June 10\nLocation: Hall\n\nhttps://invite.test/e/01HYEXAMPLE');
    assert.equal(message.match(/https:\/\/invite\.test\/e\/01HYEXAMPLE/g)?.length, 1);
});

test('whatsapp URL encodes accents, emoji, ampersands, hashes, percents, and line breaks once', () => {
    const message = 'Você & me 🎉\nApto #20\n50% confirmed?';

    assert.equal(buildWhatsAppUrl(message), `https://wa.me/?text=${encodeURIComponent(message)}`);
});

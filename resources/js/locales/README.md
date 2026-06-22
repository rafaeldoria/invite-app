# Internationalization

The application supports the canonical locales `pt-BR` and `en-US`. Brazilian Portuguese is the default. Laravel resolves the active locale from the persisted session or encrypted `locale` cookie, then the browser preference, and finally the default.

## Backend messages

Add server-side messages to the matching files under `lang/pt-BR` and `lang/en-US`. Keep keys and code in English. Domain values must remain locale-neutral.

## Frontend messages

Add the English source key and value to `en-US.ts`, then add the same key to `pt-BR.ts`. TypeScript rejects missing or unknown static keys, and `npm run test:frontend` verifies catalog keys and interpolation variables.

Use named variables with braces:

```ts
t('guests.count.one', { count: 1 });
```

Use `tp` for the supported singular/plural pairs:

```ts
tp('guests.count', guestCount);
```

Translation values are plain text and must never contain trusted HTML.

## Formatting

Use the helpers in `resources/js/utils/formatting.ts`. Date and time calls require an explicit IANA timezone so event values never shift according to the viewer's browser timezone.

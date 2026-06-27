import { useForm } from '@inertiajs/react';
import { useMemo, useState } from 'react';
import { Alert } from '../feedback/Alert';
import { Field } from '../forms/Field';
import { Textarea } from '../forms/controls';
import { Button } from '../ui/Button';
import { Card } from '../ui/Card';
import { useLocale } from '../../hooks/use-locale';
import type { EventShare } from '../../types/events';
import { buildShareMessage, buildWhatsAppUrl } from '../../utils/share-message';

type Feedback = { tone: 'success' | 'error'; message: string } | null;

export function SharePanel({ share }: { share: EventShare }) {
    const { t } = useLocale();
    const form = useForm<{ share_message: string }>({
        share_message: share.custom_message ?? '',
    });
    const [feedback, setFeedback] = useState<Feedback>(null);
    const finalMessage = useMemo(() => buildShareMessage(form.data.share_message.trim() || share.default_message, share.summary, share.canonical_url), [form.data.share_message, share]);
    const whatsappUrl = useMemo(() => buildWhatsAppUrl(finalMessage), [finalMessage]);
    const characters = form.data.share_message.length;
    const hasUnsavedChanges = form.data.share_message !== (share.custom_message ?? '');

    function saveMessage() {
        form.patch(share.update_url, {
            preserveScroll: true,
            onSuccess: () => setFeedback({ tone: 'success', message: t('events.share.saveSuccess') }),
            onError: () => setFeedback({ tone: 'error', message: t('events.share.saveError') }),
        });
    }

    async function copy(value: string, successMessage: string) {
        try {
            await copyText(value);
            setFeedback({ tone: 'success', message: successMessage });
        } catch {
            setFeedback({ tone: 'error', message: t('events.share.copyError') });
        }
    }

    async function shareNative() {
        if (!navigator.share) {
            setFeedback({ tone: 'error', message: t('events.share.nativeUnavailable') });
            return;
        }

        try {
            await navigator.share({
                title: share.default_message,
                text: finalMessage,
            });
            setFeedback({ tone: 'success', message: t('events.share.nativeSuccess') });
        } catch (error) {
            if (error instanceof DOMException && error.name === 'AbortError') {
                return;
            }

            setFeedback({ tone: 'error', message: t('events.share.nativeError') });
        }
    }

    function openWhatsApp() {
        window.open(whatsappUrl, '_blank', 'noopener,noreferrer');
    }

    return (
        <Card>
            <div className="space-y-5">
                <div>
                    <h2 className="text-base font-semibold text-ink">{t('events.share.title')}</h2>
                    <p className="mt-2 text-sm leading-6 text-muted">{t('events.share.description')}</p>
                </div>

                <Field id="event-share-message" label={t('events.share.customLabel')} help={t('events.share.customHelp')} error={form.errors.share_message}>
                    <Textarea
                        id="event-share-message"
                        maxLength={500}
                        value={form.data.share_message}
                        invalid={Boolean(form.errors.share_message)}
                        onChange={(event) => form.setData('share_message', event.target.value)}
                    />
                </Field>
                <p className="text-right text-xs font-medium text-muted">{t('events.share.characterCount', { count: characters, limit: 500 })}</p>

                <div className="rounded-lg bg-canvas p-4">
                    <h3 className="text-sm font-semibold text-ink">{t('events.share.previewTitle')}</h3>
                    <p className="mt-3 whitespace-pre-line break-words text-sm leading-6 text-muted">{finalMessage}</p>
                </div>

                {feedback ? (
                    <Alert title={feedback.message} tone={feedback.tone}>
                        {hasUnsavedChanges ? t('events.share.unsavedHint') : t('events.share.savedHint')}
                    </Alert>
                ) : null}

                <div className="grid gap-2">
                    <Button type="button" onClick={saveMessage} loading={form.processing} loadingLabel={t('events.share.saving')} disabled={!hasUnsavedChanges}>
                        {t('events.share.save')}
                    </Button>
                    <div className="grid gap-2 sm:grid-cols-2">
                        <Button type="button" variant="secondary" onClick={() => copy(share.canonical_url, t('events.share.copyLinkSuccess'))}>
                            {t('events.share.copyLink')}
                        </Button>
                        <Button type="button" variant="secondary" onClick={() => copy(finalMessage, t('events.share.copyMessageSuccess'))}>
                            {t('events.share.copyMessage')}
                        </Button>
                        <Button type="button" variant="secondary" onClick={openWhatsApp}>
                            {t('events.share.whatsapp')}
                        </Button>
                        <Button type="button" variant="secondary" onClick={shareNative}>
                            {t('events.share.native')}
                        </Button>
                    </div>
                </div>
            </div>
        </Card>
    );
}

async function copyText(value: string): Promise<void> {
    if (navigator.clipboard?.writeText) {
        await navigator.clipboard.writeText(value);
        return;
    }

    const textarea = document.createElement('textarea');
    textarea.value = value;
    textarea.setAttribute('readonly', 'true');
    textarea.style.position = 'fixed';
    textarea.style.inset = '0 auto auto 0';
    textarea.style.opacity = '0';
    document.body.appendChild(textarea);
    textarea.select();

    try {
        if (!document.execCommand('copy')) {
            throw new Error('Copy command failed.');
        }
    } finally {
        document.body.removeChild(textarea);
    }
}

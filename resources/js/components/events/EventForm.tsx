import { router, useForm } from '@inertiajs/react';
import { useEffect, useMemo, useRef, useState, type FormEvent } from 'react';
import { Alert } from '../feedback/Alert';
import { Field } from '../forms/Field';
import { Select, Textarea, TextInput } from '../forms/controls';
import { FormErrorSummary } from '../forms/FormErrorSummary';
import { Button } from '../ui/Button';
import type { EventCoverImage, EventDetail, EventFormData, TimezoneOption } from '../../types/events';
import type { TranslationKey } from '../../locales';

const maxCoverBytes = 5 * 1024 * 1024;
const allowedCoverTypes = ['image/jpeg', 'image/png', 'image/webp'];

type EventFormProps = {
    mode: 'create' | 'edit';
    submitUrl: string;
    indexUrl: string;
    event?: EventDetail;
    timezoneOptions: TimezoneOption[];
    defaultTimezone: string;
    t: (key: TranslationKey, replacements?: Record<string, string | number>) => string;
};

function buildInitialData(event: EventDetail | undefined, defaultTimezone: string): EventFormData {
    return {
        name: event?.name ?? '',
        description: event?.description ?? '',
        starts_date: event?.starts_date ?? '',
        starts_time: event?.starts_time ?? '',
        timezone: event?.timezone ?? defaultTimezone,
        location: event?.location ?? '',
        theme: event?.theme ?? '',
        cover_image: null,
        remove_cover_image: false,
    };
}

export function EventForm({ mode, submitUrl, indexUrl, event, timezoneOptions, defaultTimezone, t }: EventFormProps) {
    const form = useForm<EventFormData>(buildInitialData(event, defaultTimezone));
    const fileInputRef = useRef<HTMLInputElement>(null);
    const [previewUrl, setPreviewUrl] = useState<string | null>(null);
    const [fileError, setFileError] = useState<string | null>(null);
    const currentCover = event?.cover_image ?? null;
    const hasVisibleCover = previewUrl !== null || (currentCover?.url !== null && !form.data.remove_cover_image);
    const coverAlt = event?.name ? t('events.coverAlt', { name: event.name }) : '';
    const formTitle = mode === 'create' ? t('events.create.title') : t('events.edit.title');

    const errors = useMemo(() => {
        const fieldIds: Partial<Record<keyof EventFormData, string>> = {
            name: 'event-name',
            description: 'event-description',
            starts_date: 'event-starts-date',
            starts_time: 'event-starts-time',
            timezone: 'event-timezone',
            location: 'event-location',
            theme: 'event-theme',
            cover_image: 'event-cover-image',
        };

        const fieldErrors = Object.entries(form.errors)
            .map(([field, message]) => ({
                fieldId: fieldIds[field as keyof EventFormData] ?? 'event-form-title',
                message,
            }))
            .filter((error): error is { fieldId: string; message: string } => Boolean(error.message));

        return fileError ? [...fieldErrors, { fieldId: 'event-cover-image', message: fileError }] : fieldErrors;
    }, [fileError, form.errors]);

    useEffect(() => {
        if (!form.isDirty) return;

        function warnBeforeLeave(event: BeforeUnloadEvent) {
            event.preventDefault();
        }

        window.addEventListener('beforeunload', warnBeforeLeave);

        return () => window.removeEventListener('beforeunload', warnBeforeLeave);
    }, [form.isDirty]);

    useEffect(() => {
        return () => {
            if (previewUrl) URL.revokeObjectURL(previewUrl);
        };
    }, [previewUrl]);

    function selectCover(file: File | null) {
        setFileError(null);

        if (previewUrl) {
            URL.revokeObjectURL(previewUrl);
            setPreviewUrl(null);
        }

        if (!file) {
            form.setData('cover_image', null);

            return;
        }

        if (!allowedCoverTypes.includes(file.type)) {
            setFileError(t('events.form.coverTypeError'));
            form.setData('cover_image', null);

            if (fileInputRef.current) fileInputRef.current.value = '';

            return;
        }

        if (file.size > maxCoverBytes) {
            setFileError(t('events.form.coverSizeError'));
            form.setData('cover_image', null);

            if (fileInputRef.current) fileInputRef.current.value = '';

            return;
        }

        form.setData({
            ...form.data,
            cover_image: file,
            remove_cover_image: false,
        });
        setPreviewUrl(URL.createObjectURL(file));
    }

    function removeSelectedCover() {
        if (previewUrl) {
            URL.revokeObjectURL(previewUrl);
            setPreviewUrl(null);
        }

        form.setData({
            ...form.data,
            cover_image: null,
            remove_cover_image: currentCover !== null,
        });
        setFileError(null);

        if (fileInputRef.current) fileInputRef.current.value = '';
    }

    function submit(submitEvent: FormEvent<HTMLFormElement>) {
        submitEvent.preventDefault();

        const options = {
            forceFormData: true,
            preserveScroll: true,
            onSuccess: () => {
                if (previewUrl) {
                    URL.revokeObjectURL(previewUrl);
                    setPreviewUrl(null);
                }
            },
        };

        if (mode === 'edit') {
            form.transform((data) => ({ ...data, _method: 'patch' }));
            form.post(submitUrl, options);

            return;
        }

        form.post(submitUrl, options);
    }

    function cancel() {
        if (!form.isDirty || window.confirm(t('events.form.discardChanges'))) {
            router.visit(indexUrl);
        }
    }

    return (
        <form onSubmit={submit} className="space-y-6" encType="multipart/form-data" aria-labelledby="event-form-title">
            <div>
                <h1 id="event-form-title" className="text-2xl font-bold tracking-[-0.02em] text-ink">{formTitle}</h1>
                <p className="mt-2 max-w-2xl text-sm leading-6 text-muted">{t('events.form.description')}</p>
            </div>

            {errors.length > 0 ? (
                <FormErrorSummary title={t('auth.formErrorTitle')} errors={errors} />
            ) : null}

            <div className="grid gap-5 lg:grid-cols-[minmax(0,1fr)_minmax(280px,360px)] lg:items-start">
                <div className="space-y-5 rounded-xl bg-surface p-5 shadow-sm sm:p-6">
                    <Field id="event-name" label={t('events.form.name')} required error={form.errors.name}>
                        <TextInput id="event-name" value={form.data.name} maxLength={120} invalid={Boolean(form.errors.name)} onChange={(change) => form.setData('name', change.target.value)} autoComplete="off" />
                    </Field>

                    <Field id="event-description" label={t('events.form.eventDescription')} required help={t('events.form.descriptionHelp')} error={form.errors.description}>
                        <Textarea id="event-description" value={form.data.description} maxLength={2000} invalid={Boolean(form.errors.description)} onChange={(change) => form.setData('description', change.target.value)} />
                    </Field>

                    <div className="grid gap-5 sm:grid-cols-2">
                        <Field id="event-starts-date" label={t('events.form.date')} required error={form.errors.starts_date}>
                            <TextInput id="event-starts-date" type="date" value={form.data.starts_date} invalid={Boolean(form.errors.starts_date)} onChange={(change) => form.setData('starts_date', change.target.value)} />
                        </Field>
                        <Field id="event-starts-time" label={t('events.form.time')} required error={form.errors.starts_time}>
                            <TextInput id="event-starts-time" type="time" value={form.data.starts_time} invalid={Boolean(form.errors.starts_time)} onChange={(change) => form.setData('starts_time', change.target.value)} />
                        </Field>
                    </div>

                    <Field id="event-timezone" label={t('events.form.timezone')} required help={t('events.form.timezoneHelp')} error={form.errors.timezone}>
                        <Select id="event-timezone" value={form.data.timezone} invalid={Boolean(form.errors.timezone)} onChange={(change) => form.setData('timezone', change.target.value)}>
                            {timezoneOptions.map((timezone) => <option key={timezone.value} value={timezone.value}>{timezone.label}</option>)}
                        </Select>
                    </Field>

                    <Field id="event-location" label={t('events.form.location')} required error={form.errors.location}>
                        <TextInput id="event-location" value={form.data.location} maxLength={255} invalid={Boolean(form.errors.location)} onChange={(change) => form.setData('location', change.target.value)} autoComplete="street-address" />
                    </Field>

                    <Field id="event-theme" label={t('events.form.theme')} help={t('events.form.themeHelp')} error={form.errors.theme}>
                        <TextInput id="event-theme" value={form.data.theme} maxLength={80} invalid={Boolean(form.errors.theme)} onChange={(change) => form.setData('theme', change.target.value)} />
                    </Field>
                </div>

                <aside className="space-y-4 rounded-xl bg-surface p-5 shadow-sm sm:p-6" aria-label={t('events.form.coverSection')}>
                    <div>
                        <h2 className="text-base font-semibold text-ink">{t('events.form.coverSection')}</h2>
                        <p className="mt-1 text-sm leading-6 text-muted">{t('events.form.coverHelp')}</p>
                    </div>

                    <CoverPreview cover={currentCover} previewUrl={previewUrl} alt={coverAlt} removed={form.data.remove_cover_image} emptyLabel={t('events.form.coverEmpty')} />

                    <Field id="event-cover-image" label={t('events.form.coverImage')} error={form.errors.cover_image ?? fileError ?? undefined}>
                        <TextInput ref={fileInputRef} id="event-cover-image" type="file" accept="image/jpeg,image/png,image/webp" invalid={Boolean(form.errors.cover_image || fileError)} onChange={(change) => selectCover(change.target.files?.[0] ?? null)} />
                    </Field>

                    {hasVisibleCover ? (
                        <Button type="button" variant="secondary" className="w-full" onClick={removeSelectedCover}>
                            {previewUrl ? t('events.form.clearSelectedCover') : t('events.form.removeCover')}
                        </Button>
                    ) : null}

                    {form.progress ? (
                        <div role="status" aria-live="polite">
                            <div className="h-2 overflow-hidden rounded-full bg-surface-muted">
                                <div className="h-full bg-accent transition-[width]" style={{ width: `${form.progress.percentage ?? 0}%` }} />
                            </div>
                            <p className="mt-2 text-sm text-muted">{t('events.form.uploadProgress', { progress: form.progress.percentage ?? 0 })}</p>
                        </div>
                    ) : null}
                </aside>
            </div>

            {form.wasSuccessful ? (
                <Alert title={t('events.form.savedTitle')} tone="success">{t('events.form.savedDescription')}</Alert>
            ) : null}

            <div className="flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
                <Button type="button" variant="secondary" onClick={cancel}>{t('events.form.cancel')}</Button>
                <Button type="submit" loading={form.processing} loadingLabel={mode === 'create' ? t('events.form.creating') : t('events.form.updating')}>
                    {mode === 'create' ? t('events.form.create') : t('events.form.update')}
                </Button>
            </div>
        </form>
    );
}

function CoverPreview({ cover, previewUrl, alt, removed, emptyLabel }: { cover: EventCoverImage | null; previewUrl: string | null; alt: string; removed: boolean; emptyLabel: string }) {
    const imageUrl = previewUrl ?? (!removed ? cover?.url : null);

    if (!imageUrl) {
        return (
            <div className="flex aspect-[4/3] items-center justify-center rounded-lg border border-dashed border-border-strong bg-canvas p-4 text-center text-sm font-medium text-muted">
                {emptyLabel}
            </div>
        );
    }

    return (
        <img src={imageUrl} alt={alt} className="aspect-[4/3] w-full rounded-lg bg-canvas object-cover" />
    );
}

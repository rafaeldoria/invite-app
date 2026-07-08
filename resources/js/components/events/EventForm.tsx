import { router, useForm } from '@inertiajs/react';
import { useEffect, useMemo, useRef, useState, type FormEvent, type KeyboardEvent, type PointerEvent, type WheelEvent } from 'react';
import { Alert } from '../feedback/Alert';
import { Field } from '../forms/Field';
import { Select, Textarea, TextInput } from '../forms/controls';
import { FormErrorSummary } from '../forms/FormErrorSummary';
import { Button } from '../ui/Button';
import { formatFormDate, formatFormTime, parseFormDateInput, parseFormTimeInput } from '../../utils/formatting';
import type { EventCoverImage, EventDetail, EventFormData, TimezoneOption } from '../../types/events';
import { translate, type TranslationKey } from '../../locales';
import type { Locale } from '../../types/shared';

const maxCoverBytes = 5 * 1024 * 1024;
const allowedCoverTypes = ['image/jpeg', 'image/png', 'image/webp'];
const defaultCoverAdjustment: CoverAdjustment = {
    zoom: 1,
    positionX: 50,
    positionY: 50,
};
const coverOutputSize = { width: 1600, height: 900 };

type CoverAdjustment = {
    zoom: number;
    positionX: number;
    positionY: number;
};

type CoverDragState = {
    pointerId: number;
    x: number;
    y: number;
    positionX: number;
    positionY: number;
};

type EventFormProps = {
    mode: 'create' | 'edit';
    submitUrl: string;
    indexUrl: string;
    event?: EventDetail;
    timezoneOptions: TimezoneOption[];
    defaultTimezone: string;
    locale: Locale;
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

function normalizeCoverAdjustment(current: CoverAdjustment, nextAdjustment: Partial<CoverAdjustment>): CoverAdjustment {
    return {
        ...current,
        ...nextAdjustment,
        zoom: clamp(nextAdjustment.zoom ?? current.zoom, 1, 3),
        positionX: clamp(nextAdjustment.positionX ?? current.positionX, 0, 100),
        positionY: clamp(nextAdjustment.positionY ?? current.positionY, 0, 100),
    };
}

function coverAdjustmentsMatch(firstAdjustment: CoverAdjustment, secondAdjustment: CoverAdjustment): boolean {
    return firstAdjustment.zoom === secondAdjustment.zoom
        && firstAdjustment.positionX === secondAdjustment.positionX
        && firstAdjustment.positionY === secondAdjustment.positionY;
}

export function EventForm({ mode, submitUrl, indexUrl, event, timezoneOptions, defaultTimezone, locale, t }: EventFormProps) {
    const form = useForm<EventFormData>(buildInitialData(event, defaultTimezone));
    const fileInputRef = useRef<HTMLInputElement>(null);
    const coverProcessingIdRef = useRef(0);
    const coverDragRef = useRef<CoverDragState | null>(null);
    const coverAdjustmentRef = useRef<CoverAdjustment>(defaultCoverAdjustment);
    const [previewUrl, setPreviewUrl] = useState<string | null>(null);
    const [sourcePreviewUrl, setSourcePreviewUrl] = useState<string | null>(null);
    const [fileError, setFileError] = useState<string | null>(null);
    const [selectedCoverFile, setSelectedCoverFile] = useState<File | null>(null);
    const [coverAdjustment, setCoverAdjustment] = useState<CoverAdjustment>(defaultCoverAdjustment);
    const [isDraggingCover, setIsDraggingCover] = useState(false);
    const [isPreparingCover, setIsPreparingCover] = useState(false);
    const [startsDateInput, setStartsDateInput] = useState(() => formatFormDate(form.data.starts_date, locale));
    const [startsTimeInput, setStartsTimeInput] = useState(() => formatFormTime(form.data.starts_time, locale));
    const currentCover = event?.cover_image ?? null;
    const hasVisibleCover = sourcePreviewUrl !== null || previewUrl !== null || (currentCover?.url !== null && !form.data.remove_cover_image);
    const coverAlt = event?.name ? t('events.coverAlt', { name: event.name }) : '';
    const formTitle = mode === 'create' ? t('events.create.title') : t('events.edit.title');
    const datePlaceholder = locale === 'pt-BR' ? 'DD/MM/AAAA' : 'MM/DD/YYYY';
    const timePlaceholder = locale === 'pt-BR' ? '18:00' : '6:00 PM';
    const coverIsNotReady = selectedCoverFile !== null && form.data.cover_image === null;

    useEffect(() => {
        setStartsDateInput(formatFormDate(form.data.starts_date, locale));
        setStartsTimeInput(formatFormTime(form.data.starts_time, locale));
    }, [locale]);

    useEffect(() => {
        coverAdjustmentRef.current = coverAdjustment;
    }, [coverAdjustment]);

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

    useEffect(() => {
        return () => {
            if (sourcePreviewUrl) URL.revokeObjectURL(sourcePreviewUrl);
        };
    }, [sourcePreviewUrl]);

    useEffect(() => {
        if (!selectedCoverFile) return;

        const processingId = coverProcessingIdRef.current + 1;
        coverProcessingIdRef.current = processingId;
        setIsPreparingCover(true);

        const timeoutId = window.setTimeout(() => {
            prepareAdjustedCover(selectedCoverFile, coverAdjustment)
                .then((adjustedFile) => {
                    if (coverProcessingIdRef.current !== processingId) return;

                    form.setData('cover_image', adjustedFile);
                    setPreviewUrl((currentPreviewUrl) => {
                        if (currentPreviewUrl) URL.revokeObjectURL(currentPreviewUrl);

                        return URL.createObjectURL(adjustedFile);
                    });
                    setFileError(null);
                })
                .catch(() => {
                    if (coverProcessingIdRef.current !== processingId) return;

                    form.setData('cover_image', null);
                    setPreviewUrl((currentPreviewUrl) => {
                        if (currentPreviewUrl) URL.revokeObjectURL(currentPreviewUrl);

                        return null;
                    });
                    setFileError(translate(locale, 'events.form.coverProcessingError'));
                })
                .finally(() => {
                    if (coverProcessingIdRef.current === processingId) {
                        setIsPreparingCover(false);
                    }
                });
        }, 120);

        return () => {
            window.clearTimeout(timeoutId);

            if (coverProcessingIdRef.current === processingId) {
                coverProcessingIdRef.current += 1;
            }
        };
    }, [selectedCoverFile, coverAdjustment, locale]);

    function selectCover(file: File | null) {
        coverProcessingIdRef.current += 1;
        setIsPreparingCover(false);
        setFileError(null);

        if (previewUrl) {
            URL.revokeObjectURL(previewUrl);
            setPreviewUrl(null);
        }

        if (sourcePreviewUrl) {
            URL.revokeObjectURL(sourcePreviewUrl);
            setSourcePreviewUrl(null);
        }

        if (!file) {
            setSelectedCoverFile(null);
            form.setData('cover_image', null);

            return;
        }

        if (!allowedCoverTypes.includes(file.type)) {
            setSelectedCoverFile(null);
            setIsPreparingCover(false);
            setFileError(t('events.form.coverTypeError'));
            form.setData('cover_image', null);

            if (fileInputRef.current) fileInputRef.current.value = '';

            return;
        }

        if (file.size > maxCoverBytes) {
            setSelectedCoverFile(null);
            setIsPreparingCover(false);
            setFileError(t('events.form.coverSizeError'));
            form.setData('cover_image', null);

            if (fileInputRef.current) fileInputRef.current.value = '';

            return;
        }

        setCoverAdjustment(defaultCoverAdjustment);
        coverAdjustmentRef.current = defaultCoverAdjustment;
        setIsPreparingCover(true);
        setSelectedCoverFile(file);
        setSourcePreviewUrl(URL.createObjectURL(file));
        form.setData({
            ...form.data,
            cover_image: null,
            remove_cover_image: false,
        });
    }

    function removeSelectedCover() {
        const hadSelectedReplacement = selectedCoverFile !== null || previewUrl !== null;

        coverProcessingIdRef.current += 1;
        setSelectedCoverFile(null);
        setIsPreparingCover(false);
        setIsDraggingCover(false);
        setCoverAdjustment(defaultCoverAdjustment);
        coverAdjustmentRef.current = defaultCoverAdjustment;

        if (previewUrl) {
            URL.revokeObjectURL(previewUrl);
            setPreviewUrl(null);
        }

        if (sourcePreviewUrl) {
            URL.revokeObjectURL(sourcePreviewUrl);
            setSourcePreviewUrl(null);
        }

        form.setData({
            ...form.data,
            cover_image: null,
            remove_cover_image: currentCover !== null && !hadSelectedReplacement,
        });
        setFileError(null);

        if (fileInputRef.current) fileInputRef.current.value = '';
    }

    function updateCoverAdjustment(nextAdjustment: Partial<CoverAdjustment>) {
        const currentAdjustment = coverAdjustmentRef.current;
        const adjustedCoverAdjustment = normalizeCoverAdjustment(currentAdjustment, nextAdjustment);

        if (coverAdjustmentsMatch(currentAdjustment, adjustedCoverAdjustment)) {
            return;
        }

        coverAdjustmentRef.current = adjustedCoverAdjustment;

        if (selectedCoverFile) {
            coverProcessingIdRef.current += 1;
            setIsPreparingCover(true);
            form.setData('cover_image', null);
        }

        setCoverAdjustment(adjustedCoverAdjustment);
    }

    function zoomCover(delta: number) {
        updateCoverAdjustment({ zoom: Number((coverAdjustment.zoom + delta).toFixed(2)) });
    }

    function wheelCover(event: WheelEvent<HTMLDivElement>) {
        if (!selectedCoverFile) return;

        event.preventDefault();
        zoomCover(clamp(-event.deltaY * 0.001, -0.12, 0.12));
    }

    function startCoverDrag(event: PointerEvent<HTMLDivElement>) {
        if (!selectedCoverFile) return;

        event.currentTarget.setPointerCapture(event.pointerId);
        coverDragRef.current = {
            pointerId: event.pointerId,
            x: event.clientX,
            y: event.clientY,
            positionX: coverAdjustment.positionX,
            positionY: coverAdjustment.positionY,
        };
        setIsDraggingCover(true);
    }

    function moveCoverDrag(event: PointerEvent<HTMLDivElement>) {
        const drag = coverDragRef.current;

        if (!drag || drag.pointerId !== event.pointerId) return;

        const bounds = event.currentTarget.getBoundingClientRect();
        const deltaX = ((event.clientX - drag.x) / Math.max(bounds.width, 1)) * 100;
        const deltaY = ((event.clientY - drag.y) / Math.max(bounds.height, 1)) * 100;

        updateCoverAdjustment({
            positionX: drag.positionX - deltaX,
            positionY: drag.positionY - deltaY,
        });
    }

    function stopCoverDrag(event: PointerEvent<HTMLDivElement>) {
        const drag = coverDragRef.current;

        if (drag?.pointerId === event.pointerId) {
            coverDragRef.current = null;
            setIsDraggingCover(false);
        }
    }

    function keyCover(event: KeyboardEvent<HTMLDivElement>) {
        if (!selectedCoverFile) return;

        const step = event.shiftKey ? 10 : 4;

        if (event.key === '+' || event.key === '=') {
            event.preventDefault();
            zoomCover(0.08);
        } else if (event.key === '-' || event.key === '_') {
            event.preventDefault();
            zoomCover(-0.08);
        } else if (event.key === 'ArrowLeft') {
            event.preventDefault();
            updateCoverAdjustment({ positionX: coverAdjustment.positionX - step });
        } else if (event.key === 'ArrowRight') {
            event.preventDefault();
            updateCoverAdjustment({ positionX: coverAdjustment.positionX + step });
        } else if (event.key === 'ArrowUp') {
            event.preventDefault();
            updateCoverAdjustment({ positionY: coverAdjustment.positionY - step });
        } else if (event.key === 'ArrowDown') {
            event.preventDefault();
            updateCoverAdjustment({ positionY: coverAdjustment.positionY + step });
        }
    }

    function submit(submitEvent: FormEvent<HTMLFormElement>) {
        submitEvent.preventDefault();

        if (fileError || isPreparingCover || coverIsNotReady) {
            fileInputRef.current?.focus();

            return;
        }

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
        const transformEventData = (data: EventFormData) => ({
            ...data,
            starts_date: parseFormDateInput(startsDateInput, locale) ?? '',
            starts_time: parseFormTimeInput(startsTimeInput) ?? '',
        });

        if (mode === 'edit') {
            form.transform((data) => ({ ...transformEventData(data), _method: 'patch' }));
            form.post(submitUrl, options);

            return;
        }

        form.transform(transformEventData);
        form.post(submitUrl, options);
    }

    function changeStartsDate(value: string) {
        setStartsDateInput(value);

        const parsedDate = parseFormDateInput(value, locale);

        if (parsedDate !== null) {
            form.setData('starts_date', parsedDate);
        }
    }

    function blurStartsDate() {
        const parsedDate = parseFormDateInput(startsDateInput, locale);

        if (parsedDate === null) {
            form.setData('starts_date', '');

            return;
        }

        form.setData('starts_date', parsedDate);
        setStartsDateInput(formatFormDate(parsedDate, locale));
    }

    function changeStartsTime(value: string) {
        setStartsTimeInput(value);

        const parsedTime = parseFormTimeInput(value);

        if (parsedTime !== null) {
            form.setData('starts_time', parsedTime);
        }
    }

    function blurStartsTime() {
        const parsedTime = parseFormTimeInput(startsTimeInput);

        if (parsedTime === null) {
            form.setData('starts_time', '');

            return;
        }

        form.setData('starts_time', parsedTime);
        setStartsTimeInput(formatFormTime(parsedTime, locale));
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
                            <TextInput id="event-starts-date" type="text" inputMode="numeric" placeholder={datePlaceholder} value={startsDateInput} invalid={Boolean(form.errors.starts_date)} onChange={(change) => changeStartsDate(change.target.value)} onBlur={blurStartsDate} autoComplete="off" />
                        </Field>
                        <Field id="event-starts-time" label={t('events.form.time')} required error={form.errors.starts_time}>
                            <TextInput id="event-starts-time" type="text" placeholder={timePlaceholder} value={startsTimeInput} invalid={Boolean(form.errors.starts_time)} onChange={(change) => changeStartsTime(change.target.value)} onBlur={blurStartsTime} autoComplete="off" />
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

                    <CoverPreview
                        cover={currentCover}
                        sourcePreviewUrl={sourcePreviewUrl}
                        previewUrl={previewUrl}
                        alt={coverAlt}
                        removed={form.data.remove_cover_image}
                        emptyLabel={t('events.form.coverEmpty')}
                        adjustHint={t('events.form.coverAdjustHint')}
                        preparingLabel={t('events.form.coverPreparing')}
                        adjustment={coverAdjustment}
                        editable={selectedCoverFile !== null}
                        isDragging={isDraggingCover}
                        isPreparing={isPreparingCover}
                        onWheel={wheelCover}
                        onPointerDown={startCoverDrag}
                        onPointerMove={moveCoverDrag}
                        onPointerUp={stopCoverDrag}
                        onPointerCancel={stopCoverDrag}
                        onKeyDown={keyCover}
                    />

                    <Field id="event-cover-image" label={t('events.form.coverImage')} error={form.errors.cover_image ?? fileError ?? undefined}>
                        <TextInput ref={fileInputRef} id="event-cover-image" type="file" accept="image/jpeg,image/png,image/webp" invalid={Boolean(form.errors.cover_image || fileError)} onChange={(change) => selectCover(change.target.files?.[0] ?? null)} />
                    </Field>

                    {hasVisibleCover ? (
                        <Button type="button" variant="secondary" className="w-full" onClick={removeSelectedCover}>
                            {selectedCoverFile || previewUrl ? t('events.form.clearSelectedCover') : t('events.form.removeCover')}
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
                <Button type="submit" loading={form.processing} loadingLabel={mode === 'create' ? t('events.form.creating') : t('events.form.updating')} disabled={isPreparingCover || coverIsNotReady}>
                    {mode === 'create' ? t('events.form.create') : t('events.form.update')}
                </Button>
            </div>
        </form>
    );
}

function CoverPreview({
    cover,
    sourcePreviewUrl,
    previewUrl,
    alt,
    removed,
    emptyLabel,
    adjustHint,
    preparingLabel,
    adjustment,
    editable,
    isDragging,
    isPreparing,
    onWheel,
    onPointerDown,
    onPointerMove,
    onPointerUp,
    onPointerCancel,
    onKeyDown,
}: {
    cover: EventCoverImage | null;
    sourcePreviewUrl: string | null;
    previewUrl: string | null;
    alt: string;
    removed: boolean;
    emptyLabel: string;
    adjustHint: string;
    preparingLabel: string;
    adjustment: CoverAdjustment;
    editable: boolean;
    isDragging: boolean;
    isPreparing: boolean;
    onWheel: (event: WheelEvent<HTMLDivElement>) => void;
    onPointerDown: (event: PointerEvent<HTMLDivElement>) => void;
    onPointerMove: (event: PointerEvent<HTMLDivElement>) => void;
    onPointerUp: (event: PointerEvent<HTMLDivElement>) => void;
    onPointerCancel: (event: PointerEvent<HTMLDivElement>) => void;
    onKeyDown: (event: KeyboardEvent<HTMLDivElement>) => void;
}) {
    const imageUrl = sourcePreviewUrl ?? previewUrl ?? (!removed ? cover?.url : null);

    if (!imageUrl) {
        return (
            <div className="flex aspect-video items-center justify-center rounded-lg border border-dashed border-border-strong bg-canvas p-4 text-center text-sm font-medium text-muted">
                {emptyLabel}
            </div>
        );
    }

    if (editable) {
        return (
            <div
                className={`group relative aspect-video w-full overflow-hidden rounded-lg border border-border bg-canvas outline-none focus-visible:ring-2 focus-visible:ring-focus/70 ${isDragging ? 'cursor-grabbing' : 'cursor-grab'} touch-none`}
                role="group"
                tabIndex={0}
                aria-label={adjustHint}
                onWheel={onWheel}
                onPointerDown={onPointerDown}
                onPointerMove={onPointerMove}
                onPointerUp={onPointerUp}
                onPointerCancel={onPointerCancel}
                onKeyDown={onKeyDown}
            >
                <img
                    src={imageUrl}
                    alt={alt}
                    draggable={false}
                    className="h-full w-full select-none object-cover transition-transform duration-150 ease-out motion-reduce:transition-none"
                    style={{
                        objectPosition: `${adjustment.positionX}% ${adjustment.positionY}%`,
                        transform: `scale(${adjustment.zoom})`,
                        transformOrigin: `${adjustment.positionX}% ${adjustment.positionY}%`,
                    }}
                />
                <div className="pointer-events-none absolute inset-x-2 bottom-2 rounded-md bg-black/70 px-3 py-2 text-xs font-medium leading-5 text-white opacity-90 transition-opacity group-focus-within:opacity-100 sm:opacity-0 sm:group-hover:opacity-100">
                    {isPreparing ? preparingLabel : adjustHint}
                </div>
            </div>
        );
    }

    return (
        <img src={imageUrl} alt={alt} className="aspect-video w-full rounded-lg bg-canvas object-cover" />
    );
}

function prepareAdjustedCover(file: File, adjustment: CoverAdjustment): Promise<File> {
    return loadImage(file).then((image) => {
        const canvas = document.createElement('canvas');
        canvas.width = coverOutputSize.width;
        canvas.height = coverOutputSize.height;

        const context = canvas.getContext('2d');

        if (!context) {
            throw new Error('Canvas is unavailable.');
        }

        const scale = Math.max(coverOutputSize.width / image.naturalWidth, coverOutputSize.height / image.naturalHeight) * adjustment.zoom;
        const drawWidth = image.naturalWidth * scale;
        const drawHeight = image.naturalHeight * scale;
        const offsetX = -(drawWidth - coverOutputSize.width) * (adjustment.positionX / 100);
        const offsetY = -(drawHeight - coverOutputSize.height) * (adjustment.positionY / 100);

        context.drawImage(image, offsetX, offsetY, drawWidth, drawHeight);

        return new Promise<File>((resolve, reject) => {
            canvas.toBlob((blob) => {
                if (!blob) {
                    reject(new Error('Canvas output is unavailable.'));

                    return;
                }

                resolve(new File([blob], file.name, {
                    type: 'image/jpeg',
                    lastModified: Date.now(),
                }));
            }, 'image/jpeg', 0.86);
        }).then((adjustedFile) => {
            if (adjustedFile.size > maxCoverBytes) {
                throw new Error('Adjusted cover image is too large.');
            }

            return new File([adjustedFile], replaceFileExtension(file.name, 'jpg'), {
                type: 'image/jpeg',
                lastModified: adjustedFile.lastModified,
            });
        });
    });
}

function clamp(value: number, min: number, max: number): number {
    return Math.min(Math.max(value, min), max);
}

function replaceFileExtension(filename: string, extension: string): string {
    const basename = filename.replace(/\.[^.]+$/, '');

    return `${basename || 'cover-image'}.${extension}`;
}

function loadImage(file: File): Promise<HTMLImageElement> {
    return new Promise((resolve, reject) => {
        const image = new Image();
        const url = URL.createObjectURL(file);

        image.onload = () => {
            URL.revokeObjectURL(url);
            resolve(image);
        };
        image.onerror = () => {
            URL.revokeObjectURL(url);
            reject(new Error('Image could not be loaded.'));
        };
        image.src = url;
    });
}

import { Head } from '@inertiajs/react';
import { EventForm } from '../../components/events/EventForm';
import { AuthenticatedLayout } from '../../layouts/AuthenticatedLayout';
import { useLocale } from '../../hooks/use-locale';
import type { EventDetail, TimezoneOption } from '../../types/events';

export default function Edit({ event, timezoneOptions }: { event: EventDetail; timezoneOptions: TimezoneOption[] }) {
    const { locale, t } = useLocale();

    return (
        <AuthenticatedLayout>
            <Head title={t('events.edit.title')} />
            <main id="main-content" className="mx-auto w-full max-w-7xl px-5 py-8 sm:py-10">
                <EventForm mode="edit" submitUrl={event.links.update ?? ''} indexUrl={event.links.index ?? '/events'} event={event} timezoneOptions={timezoneOptions} defaultTimezone={event.timezone} locale={locale} t={t} />
            </main>
        </AuthenticatedLayout>
    );
}

import { Head } from '@inertiajs/react';
import { EventForm } from '../../components/events/EventForm';
import { AuthenticatedLayout } from '../../layouts/AuthenticatedLayout';
import { useLocale } from '../../hooks/use-locale';
import type { TimezoneOption } from '../../types/events';

export default function Create({ defaults, timezoneOptions, links }: { defaults: { timezone: string }; timezoneOptions: TimezoneOption[]; links: { store: string; index: string } }) {
    const { t } = useLocale();

    return (
        <AuthenticatedLayout>
            <Head title={t('events.create.title')} />
            <main id="main-content" className="mx-auto w-full max-w-7xl px-5 py-8 sm:py-10">
                <EventForm mode="create" submitUrl={links.store} indexUrl={links.index} timezoneOptions={timezoneOptions} defaultTimezone={defaults.timezone} t={t} />
            </main>
        </AuthenticatedLayout>
    );
}

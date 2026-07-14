import { Head, usePage } from '@inertiajs/react';
import { useRef } from 'react';
import { ButtonLink } from '../components/ui/Button';
import { StatusBadge } from '../components/ui/StatusBadge';
import { useLocale } from '../hooks/use-locale';
import { AuthenticatedLayout } from '../layouts/AuthenticatedLayout';
import { PublicLayout } from '../layouts/PublicLayout';
import type { SharedPageProps } from '../types/shared';

type Step = {
    number: string;
    title: string;
    description: string;
};

type Feature = {
    title: string;
    description: string;
};

type Testimonial = {
    quote: string;
    name: string;
    context: string;
};

export default function Welcome() {
    const { auth } = usePage<SharedPageProps>().props;
    const { t } = useLocale();
    const testimonialsRef = useRef<HTMLDivElement>(null);
    const primaryHref = auth.user ? '/events' : '/login';
    const primaryLabel = auth.user ? t('welcome.heroPrimaryAuthenticated') : t('welcome.heroPrimary');

    const steps: Step[] = [
        {
            number: '1',
            title: t('welcome.stepCreateTitle'),
            description: t('welcome.stepCreateDescription'),
        },
        {
            number: '2',
            title: t('welcome.stepInviteTitle'),
            description: t('welcome.stepInviteDescription'),
        },
        {
            number: '3',
            title: t('welcome.stepTrackTitle'),
            description: t('welcome.stepTrackDescription'),
        },
    ];

    const features: Feature[] = [
        {
            title: t('welcome.featureEventTitle'),
            description: t('welcome.featureEventDescription'),
        },
        {
            title: t('welcome.featureGuestsTitle'),
            description: t('welcome.featureGuestsDescription'),
        },
        {
            title: t('welcome.featureRsvpTitle'),
            description: t('welcome.featureRsvpDescription'),
        },
        {
            title: t('welcome.featureDashboardTitle'),
            description: t('welcome.featureDashboardDescription'),
        },
    ];

    const testimonials: Testimonial[] = [
        {
            quote: t('welcome.testimonialOneQuote'),
            name: t('welcome.testimonialOneName'),
            context: t('welcome.testimonialOneContext'),
        },
        {
            quote: t('welcome.testimonialTwoQuote'),
            name: t('welcome.testimonialTwoName'),
            context: t('welcome.testimonialTwoContext'),
        },
        {
            quote: t('welcome.testimonialThreeQuote'),
            name: t('welcome.testimonialThreeName'),
            context: t('welcome.testimonialThreeContext'),
        },
        {
            quote: t('welcome.testimonialFourQuote'),
            name: t('welcome.testimonialFourName'),
            context: t('welcome.testimonialFourContext'),
        },
    ];

    function scrollTestimonials(direction: 'previous' | 'next') {
        const container = testimonialsRef.current;

        if (!container) return;

        const firstCard = container.querySelector<HTMLElement>('[data-testimonial-card]');
        const distance = firstCard ? firstCard.offsetWidth + 16 : container.clientWidth;
        const prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

        container.scrollBy({
            left: direction === 'next' ? distance : -distance,
            behavior: prefersReducedMotion ? 'auto' : 'smooth',
        });
    }

    const headerActions = (
        <nav aria-label={t('welcome.headerActions')} className="flex items-center gap-2">
            <a href="#contact" className="inline-flex min-h-11 items-center rounded-lg px-3 text-sm font-semibold text-muted hover:bg-surface-muted hover:text-ink focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-focus">
                {t('welcome.contact')}
            </a>
            <ButtonLink href="/login" variant="secondary" className="px-3 sm:px-4">
                {t('welcome.signIn')}
            </ButtonLink>
        </nav>
    );

    const page = (
        <>
            <Head title={t('welcome.metaTitle')}>
                <meta name="description" content={t('welcome.metaDescription')} />
            </Head>

            <main id="main-content" className="mx-auto w-full max-w-6xl px-5 py-8 sm:py-12 lg:py-14">
                <section className="grid gap-8 py-6 lg:grid-cols-[minmax(0,1fr)_25rem] lg:items-center lg:gap-12" aria-labelledby="home-hero-title">
                    <div className="max-w-3xl">
                        <p className="text-sm font-semibold text-accent-strong">{t('welcome.heroKicker')}</p>
                        <h1 id="home-hero-title" className="mt-4 max-w-3xl text-4xl font-bold tracking-[-0.03em] text-balance text-ink sm:text-5xl">
                            {t('welcome.heroTitle')}
                        </h1>
                        <p className="mt-5 max-w-[64ch] text-base leading-7 text-pretty text-muted sm:text-lg">
                            {t('welcome.heroDescription')}
                        </p>
                        <div className="mt-7 flex flex-col gap-3 sm:flex-row">
                            <ButtonLink href={primaryHref} className="w-full sm:w-auto">
                                {primaryLabel}
                            </ButtonLink>
                            <a href="#contact" className="inline-flex min-h-11 w-full items-center justify-center rounded-lg border border-border bg-surface px-4 py-2 text-sm font-semibold text-ink transition duration-150 ease-[cubic-bezier(0.25,1,0.5,1)] hover:bg-surface-muted focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-focus sm:w-auto">
                                {t('welcome.contact')}
                            </a>
                        </div>
                    </div>

                    <ProductPreview />
                </section>

                <section className="mt-12 border-y border-border py-10 sm:mt-16 sm:py-12" aria-labelledby="home-steps-title">
                    <div className="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
                        <div>
                            <h2 id="home-steps-title" className="text-2xl font-bold tracking-[-0.02em] text-ink">
                                {t('welcome.stepsTitle')}
                            </h2>
                            <p className="mt-2 max-w-2xl text-sm leading-6 text-muted">
                                {t('welcome.stepsDescription')}
                            </p>
                        </div>
                    </div>
                    <div className="mt-7 grid gap-4 md:grid-cols-3">
                        {steps.map((step) => (
                            <article key={step.number} className="rounded-xl bg-surface p-5 shadow-sm">
                                <span className="inline-flex size-9 items-center justify-center rounded-full bg-accent-soft text-sm font-bold text-accent-strong">
                                    {step.number}
                                </span>
                                <h3 className="mt-4 text-lg font-semibold text-ink">{step.title}</h3>
                                <p className="mt-2 text-sm leading-6 text-muted">{step.description}</p>
                            </article>
                        ))}
                    </div>
                </section>

                <section className="mt-10 rounded-xl bg-surface-muted px-5 py-8 sm:px-6 sm:py-10" aria-labelledby="home-features-title">
                    <h2 id="home-features-title" className="text-2xl font-bold tracking-[-0.02em] text-ink">
                        {t('welcome.featuresTitle')}
                    </h2>
                    <div className="mt-6 grid gap-4 sm:grid-cols-2">
                        {features.map((feature) => (
                            <article key={feature.title} className="rounded-lg bg-surface p-5">
                                <h3 className="text-base font-semibold text-ink">{feature.title}</h3>
                                <p className="mt-2 text-sm leading-6 text-muted">{feature.description}</p>
                            </article>
                        ))}
                    </div>
                </section>

                <section className="mt-12 sm:mt-16" aria-labelledby="home-testimonials-title">
                    <div className="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                        <div className="max-w-2xl">
                            <h2 id="home-testimonials-title" className="text-2xl font-bold tracking-[-0.02em] text-ink">
                                {t('welcome.testimonialsTitle')}
                            </h2>
                            <p className="mt-2 text-sm leading-6 text-muted">{t('welcome.testimonialsDescription')}</p>
                        </div>
                        <div className="flex gap-2" aria-label={t('welcome.testimonialsControls')}>
                            <button type="button" onClick={() => scrollTestimonials('previous')} className="flex size-11 items-center justify-center rounded-lg border border-border bg-surface text-xl font-semibold text-ink transition-colors hover:bg-surface-muted focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-focus" aria-label={t('welcome.testimonialsPrevious')}>
                                <span aria-hidden="true">‹</span>
                            </button>
                            <button type="button" onClick={() => scrollTestimonials('next')} className="flex size-11 items-center justify-center rounded-lg border border-border bg-surface text-xl font-semibold text-ink transition-colors hover:bg-surface-muted focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-focus" aria-label={t('welcome.testimonialsNext')}>
                                <span aria-hidden="true">›</span>
                            </button>
                        </div>
                    </div>
                    <div ref={testimonialsRef} className="scrollbar-none mt-7 flex snap-x snap-mandatory gap-4 overflow-x-auto pb-3" aria-live="polite">
                        {testimonials.map((testimonial) => (
                            <figure key={testimonial.name} data-testimonial-card className="flex min-h-56 shrink-0 basis-[min(21rem,85vw)] snap-start flex-col rounded-xl bg-surface p-5 shadow-sm md:basis-[calc((100%_-_1rem)/2)] lg:basis-[calc((100%_-_2rem)/3)]">
                                <div className="mb-4 flex items-center gap-1 text-warning-ink">
                                    <span className="sr-only">{t('welcome.testimonialRatingLabel')}</span>
                                    <span aria-hidden="true" className="text-base leading-none">★★★★★</span>
                                </div>
                                <blockquote className="text-sm leading-6 text-ink">
                                    <p>{testimonial.quote}</p>
                                </blockquote>
                                <figcaption className="mt-auto border-t border-border pt-4">
                                    <p className="font-semibold text-ink">{testimonial.name}</p>
                                    <p className="mt-1 text-sm text-muted">{testimonial.context}</p>
                                </figcaption>
                            </figure>
                        ))}
                    </div>
                </section>

                <section id="contact" className="mt-12 scroll-mt-24 rounded-xl bg-accent-soft p-6 sm:mt-16 sm:p-8" aria-labelledby="home-contact-title">
                    <div className="flex flex-col gap-5 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <h2 id="home-contact-title" className="text-2xl font-bold tracking-[-0.02em] text-ink">
                                {t('welcome.contactTitle')}
                            </h2>
                            <p className="mt-2 max-w-2xl text-sm leading-6 text-accent-strong">
                                {t('welcome.contactDescription')}
                            </p>
                        </div>
                        <ButtonLink href="/support" variant="secondary" className="shrink-0">
                            {t('welcome.contactStatus')}
                        </ButtonLink>
                    </div>
                </section>
            </main>

            <footer className="border-t border-border px-5 py-6">
                <div className="mx-auto flex max-w-6xl flex-col gap-2 text-sm text-muted sm:flex-row sm:items-center sm:justify-between">
                    <p className="font-semibold text-ink">{t('welcome.footerBrand')}</p>
                    <p>{t('welcome.footerDescription')}</p>
                </div>
            </footer>
        </>
    );

    if (auth.user) {
        return <AuthenticatedLayout>{page}</AuthenticatedLayout>;
    }

    return <PublicLayout headerActions={headerActions}>{page}</PublicLayout>;
}

function ProductPreview() {
    const { t } = useLocale();

    return (
        <aside className="rounded-xl bg-surface p-4 shadow-sm sm:p-5" aria-label={t('welcome.previewLabel')}>
            <div className="rounded-lg bg-canvas p-4">
                <div className="flex items-start justify-between gap-4">
                    <div>
                        <p className="text-xs font-semibold text-accent-strong">{t('welcome.previewEventType')}</p>
                        <h2 className="mt-1 text-xl font-bold tracking-[-0.02em] text-ink">{t('welcome.previewEventName')}</h2>
                    </div>
                    <span className="rounded-full bg-accent-soft px-3 py-1 text-xs font-semibold text-accent-strong">
                        {t('welcome.previewShared')}
                    </span>
                </div>

                <dl className="mt-5 grid gap-3 text-sm sm:grid-cols-2 lg:grid-cols-1">
                    <div className="rounded-lg bg-surface p-3">
                        <dt className="font-semibold text-ink">{t('events.fields.startsAt')}</dt>
                        <dd className="mt-1 text-muted">{t('welcome.previewDate')}</dd>
                    </div>
                    <div className="rounded-lg bg-surface p-3">
                        <dt className="font-semibold text-ink">{t('events.fields.location')}</dt>
                        <dd className="mt-1 text-muted">{t('welcome.previewLocation')}</dd>
                    </div>
                </dl>

                <div className="mt-5" aria-label={t('welcome.previewStatuses')}>
                    <div className="flex flex-wrap gap-2">
                        <StatusBadge status="confirmed">{t('welcome.previewConfirmed')}</StatusBadge>
                        <StatusBadge status="pending">{t('welcome.previewPending')}</StatusBadge>
                        <StatusBadge status="declined">{t('welcome.previewDeclined')}</StatusBadge>
                    </div>
                </div>
            </div>

            <div className="mt-4 rounded-lg bg-surface-muted p-3">
                <p className="text-sm font-semibold text-ink">{t('welcome.previewDashboardTitle')}</p>
                <div className="mt-3 grid gap-2">
                    <PreviewMetric label={t('welcome.previewMetricTotal')} value="42" />
                    <PreviewMetric label={t('welcome.previewMetricConfirmed')} value="28" />
                    <PreviewMetric label={t('welcome.previewMetricPending')} value="10" />
                </div>
            </div>
        </aside>
    );
}

function PreviewMetric({ label, value }: { label: string; value: string }) {
    return (
        <div className="flex items-center justify-between gap-3 rounded-lg bg-surface px-3 py-2">
            <p className="text-sm font-medium text-muted">{label}</p>
            <p className="text-sm font-bold text-ink">{value}</p>
        </div>
    );
}

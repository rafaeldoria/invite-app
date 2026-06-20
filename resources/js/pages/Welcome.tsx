import { Head } from '@inertiajs/react';

export default function Welcome() {
    return (
        <>
            <Head title="Convites" />

            <main className="flex min-h-screen items-center justify-center bg-slate-950 px-6 text-white">
                <section className="max-w-xl text-center">
                    <p className="mb-3 text-sm font-semibold uppercase tracking-[0.3em] text-rose-400">
                        Invite App
                    </p>
                    <h1 className="text-4xl font-bold tracking-tight sm:text-6xl">
                        Laravel + Inertia + React
                    </h1>
                    <p className="mt-6 text-lg leading-8 text-slate-300">
                        A base do frontend está pronta com TypeScript e Tailwind CSS.
                    </p>
                </section>
            </main>
        </>
    );
}

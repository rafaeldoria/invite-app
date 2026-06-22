import type { PageProps } from '@inertiajs/core';

export type Locale = 'pt-BR' | 'en-US';

export type AuthenticatedUser = {
    id: number;
    name: string;
    email: string;
    email_verified_at: string | null;
};

export type SharedPageProps = PageProps & {
    app: { name: string };
    auth: { user: AuthenticatedUser | null };
    flash: { success: string | null; error: string | null };
    locale: Locale;
};

export type NavigationItem = {
    label: string;
    href: string;
};
